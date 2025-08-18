<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleSheetsController extends Controller
{
    protected function getClient(): \Google\Client
    {
        $cfg = config('services.google');
        $client = new \Google\Client();
        $client->setApplicationName($cfg['app_name'] ?? 'Report System');
        $client->setClientId($cfg['client_id'] ?? '');
        $client->setClientSecret($cfg['client_secret'] ?? '');
        $client->setRedirectUri($cfg['redirect'] ?? '');
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes($cfg['scopes'] ?? []);
        return $client;
    }

    protected function tokenPath(): string
    {
        return storage_path('app/private/google_oauth_token.json');
    }

    protected function loadToken(\Google\Client $client): bool
    {
        $path = $this->tokenPath();
        if (is_file($path)) {
            $json = file_get_contents($path);
            if ($json) {
                $token = json_decode($json, true);
                if (is_array($token)) {
                    $client->setAccessToken($token);
                    if ($client->isAccessTokenExpired()) {
                        $refreshToken = $token['refresh_token'] ?? null;
                        if ($refreshToken) {
                            $client->fetchAccessTokenWithRefreshToken($refreshToken);
                            $newToken = $client->getAccessToken();
                            if (!isset($newToken['refresh_token']) && $refreshToken) {
                                $newToken['refresh_token'] = $refreshToken;
                            }
                            file_put_contents($path, json_encode($newToken));
                        } else {
                            return false;
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function auth(Request $request)
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect('/')->with('error', 'Missing authorization code');
        }
        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            return redirect('/')->with('error', 'OAuth error: ' . $token['error']);
        }
        // Ensure refresh token is preserved
        $existing = [];
        $path = $this->tokenPath();
        if (is_file($path)) {
            $existing = json_decode((string)file_get_contents($path), true) ?: [];
        }
        if (!isset($token['refresh_token']) && isset($existing['refresh_token'])) {
            $token['refresh_token'] = $existing['refresh_token'];
        }
        file_put_contents($path, json_encode($token));

        // Render a lightweight page that notifies opener and stays open
        return response()->view('google.oauth-callback');
    }

    public function createRumbleSheet(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'values' => 'required|array',
            'values.*' => 'array',
            'cadence' => 'required|in:daily,weekly,monthly',
            'date_to' => 'required|date',
        ]);

        $client = $this->getClient();
        if (!$this->loadToken($client)) {
            return response()->json([
                'authorizeUrl' => $client->createAuthUrl(),
                'message' => 'Authorization required',
            ], 401);
        }

        try {
            $drive = new \Google\Service\Drive($client);
            $sheets = new \Google\Service\Sheets($client);

            $cadence = $request->input('cadence');
            // Robust date parsing
            $rawDateTo = $request->input('date_to');
            try {
                $dateTo = $rawDateTo instanceof \DateTimeInterface ? Carbon::instance($rawDateTo) : Carbon::parse((string)$rawDateTo);
            } catch (\Throwable $e) {
                $dateTo = Carbon::now();
            }
            $year = $dateTo->format('Y');
            $title = $request->input('title');
            $values = $request->input('values');
            // Normalize each row to a sequential, scalar array to avoid JSON object encoding
            if (is_array($values)) {
                $values = array_map(function ($row) {
                    if (!is_array($row)) {
                        return [ (string) $row ];
                    }
                    // reindex keys 0..n to force JSON arrays, not objects
                    $row = array_values($row);
                    foreach ($row as $i => $cell) {
                        if ($cell === null) $row[$i] = '';
                        elseif (is_bool($cell)) $row[$i] = $cell ? 'TRUE' : 'FALSE';
                        elseif (is_array($cell) || is_object($cell)) $row[$i] = '';
                        else $row[$i] = (string) $cell;
                    }
                    return $row;
                }, $values);
            }

            $parentEnvMap = [
                'daily' => env('GOOGLE_DRIVE_DAILY_PARENT_ID'),
                'weekly' => env('GOOGLE_DRIVE_WEEKLY_PARENT_ID'),
                'monthly' => env('GOOGLE_DRIVE_MONTHLY_PARENT_ID'),
            ];
            $parentId = $parentEnvMap[$cadence] ?? null;
            if (!$parentId) {
                $parentId = env('GOOGLE_DRIVE_DEFAULT_PARENT_ID');
            }

            $supportsAllDrives = true;

            // Determine target parent: either cadence parent directly, or year subfolder when pattern=YYYY
            $targetParentId = null;
            $subPattern = env('GOOGLE_SHEET_SUBFOLDER_PATTERN');
            if ($parentId && $parentId !== '...') {
                if ($subPattern && strtoupper(trim($subPattern)) === 'YYYY') {
                    // Locate or create year subfolder under the cadence parent
                    try {
                        $q = sprintf("mimeType = 'application/vnd.google-apps.folder' and trashed = false and name = '%s' and '%s' in parents", addslashes($year), $parentId);
                        $list = $drive->files->listFiles([
                            'q' => $q,
                            'fields' => 'files(id, name)',
                            'supportsAllDrives' => $supportsAllDrives,
                            'includeItemsFromAllDrives' => $supportsAllDrives,
                            'corpora' => 'allDrives',
                            'spaces' => 'drive',
                        ]);
                        if ($list && count($list->getFiles()) > 0) {
                            $targetParentId = $list->getFiles()[0]->getId();
                        } else {
                            $folderFile = new \Google\Service\Drive\DriveFile([
                                'name' => $year,
                                'mimeType' => 'application/vnd.google-apps.folder',
                                'parents' => [$parentId],
                            ]);
                            $created = $drive->files->create($folderFile, [
                                'fields' => 'id',
                                'supportsAllDrives' => $supportsAllDrives,
                            ]);
                            $targetParentId = $created->getId();
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Drive subfolder step failed: ' . $e->getMessage());
                        $targetParentId = $parentId; // fallback to cadence parent
                    }
                } else {
                    // No subfolder requested; place file directly under cadence parent
                    $targetParentId = $parentId;
                }
            }

            // Create spreadsheet
            $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
                'properties' => [ 'title' => $title ],
            ]);
            $createdSheet = $sheets->spreadsheets->create($spreadsheet, [
                'fields' => 'spreadsheetId,spreadsheetUrl,sheets.properties.sheetId',
            ]);
            $spreadsheetId = $createdSheet->getSpreadsheetId();
            $spreadsheetUrl = $createdSheet->getSpreadsheetUrl() ?: ('https://docs.google.com/spreadsheets/d/' . $spreadsheetId);
            $firstSheetId = null;
            $sheetsArr = $createdSheet->getSheets();
            if (is_array($sheetsArr) && isset($sheetsArr[0])) {
                $firstSheetId = $sheetsArr[0]->getProperties()->getSheetId();
            }

            // Move to target folder if configured
            if ($targetParentId) {
                try {
                    $drive->files->update($spreadsheetId, new \Google\Service\Drive\DriveFile(), [
                        'addParents' => $targetParentId,
                        'supportsAllDrives' => $supportsAllDrives,
                        'fields' => 'id, parents',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Drive move to folder failed: ' . $e->getMessage());
                }
            }

            // Write values with USER_ENTERED so formulas work
            $body = new \Google\Service\Sheets\ValueRange([
                'majorDimension' => 'ROWS',
                'values' => $values,
            ]);
            $sheets->spreadsheets_values->update($spreadsheetId, 'A1', $body, [
                'valueInputOption' => 'USER_ENTERED',
            ]);

            // Formatting: header, number formats, conditional colors, labels, and column sizes (best-effort)
            if ($firstSheetId !== null) {
                $rowCount = is_array($values) ? count($values) : 0;
                $startData = 2; // zero-based, data starts on row 3 (after date and header)
                $endData = max($startData, $rowCount); // non-inclusive

                $requests = [];

                // Rename first sheet tab to 'Report'
                $requests[] = new \Google\Service\Sheets\Request([
                    'updateSheetProperties' => [
                        'properties' => [ 'sheetId' => $firstSheetId, 'title' => 'Report' ],
                        'fields' => 'title',
                    ]
                ]);

                // Bold A1 (the date row first cell)
                $requests[] = new \Google\Service\Sheets\Request([
                    'repeatCell' => [
                        'range' => [ 'sheetId' => $firstSheetId, 'startRowIndex' => 0, 'endRowIndex' => 1, 'startColumnIndex' => 0, 'endColumnIndex' => 1 ],
                        'cell' => [ 'userEnteredFormat' => [ 'textFormat' => [ 'bold' => true ] ] ],
                        'fields' => 'userEnteredFormat.textFormat.bold'
                    ]
                ]);

                // Header row 2: gray background + bold text (A..J only)
                $requests[] = new \Google\Service\Sheets\Request([
                    'repeatCell' => [
                        'range' => [ 'sheetId' => $firstSheetId, 'startRowIndex' => 1, 'endRowIndex' => 2, 'startColumnIndex' => 0, 'endColumnIndex' => 10 ],
                        'cell' => [ 'userEnteredFormat' => [ 'backgroundColor' => [ 'red' => 0.8549, 'green' => 0.8549, 'blue' => 0.8549 ], 'textFormat' => [ 'bold' => true ] ] ],
                        'fields' => 'userEnteredFormat(backgroundColor, textFormat.bold)'
                    ]
                ]);

                // Number formats for currency columns: C,D,E,I,J (2,3,4,8,9)
                foreach ([2,3,4,8,9] as $col) {
                    $requests[] = new \Google\Service\Sheets\Request([
                        'repeatCell' => [
                            'range' => [ 'sheetId' => $firstSheetId, 'startRowIndex' => $startData, 'endRowIndex' => $endData, 'startColumnIndex' => $col, 'endColumnIndex' => $col + 1 ],
                            'cell' => [ 'userEnteredFormat' => [ 'numberFormat' => [ 'type' => 'NUMBER', 'pattern' => '"$"#,##0.00' ] ] ],
                            'fields' => 'userEnteredFormat.numberFormat'
                        ]
                    ]);
                }

                // Percent format for ROI column G (index 6)
                $requests[] = new \Google\Service\Sheets\Request([
                    'repeatCell' => [
                        'range' => [ 'sheetId' => $firstSheetId, 'startRowIndex' => $startData, 'endRowIndex' => $endData, 'startColumnIndex' => 6, 'endColumnIndex' => 7 ],
                        'cell' => [ 'userEnteredFormat' => [ 'numberFormat' => [ 'type' => 'PERCENT', 'pattern' => '0.00%' ] ] ],
                        'fields' => 'userEnteredFormat.numberFormat'
                    ]
                ]);

                // Conditional formatting: P/L (F index 5) and ROI (G index 6)
                $green = [ 'red' => 0.639, 'green' => 0.855, 'blue' => 0.616 ]; // #a3da9d
                $red = [ 'red' => 1.0, 'green' => 0.502, 'blue' => 0.502 ]; // #ff8080
                foreach ([5, 6] as $colIdx) {
                    // Greater than 0 => green
                    $requests[] = new \Google\Service\Sheets\Request([
                        'addConditionalFormatRule' => [
                            'rule' => [
                                'ranges' => [[ 'sheetId' => $firstSheetId, 'startRowIndex' => $startData, 'endRowIndex' => $endData, 'startColumnIndex' => $colIdx, 'endColumnIndex' => $colIdx + 1 ]],
                                'booleanRule' => [ 'condition' => [ 'type' => 'NUMBER_GREATER', 'values' => [[ 'userEnteredValue' => '0' ]] ], 'format' => [ 'backgroundColor' => $green ] ],
                            ],
                            'index' => 0
                        ]
                    ]);
                    // Less than 0 => red
                    $requests[] = new \Google\Service\Sheets\Request([
                        'addConditionalFormatRule' => [
                            'rule' => [
                                'ranges' => [[ 'sheetId' => $firstSheetId, 'startRowIndex' => $startData, 'endRowIndex' => $endData, 'startColumnIndex' => $colIdx, 'endColumnIndex' => $colIdx + 1 ]],
                                'booleanRule' => [ 'condition' => [ 'type' => 'NUMBER_LESS', 'values' => [[ 'userEnteredValue' => '0' ]] ], 'format' => [ 'backgroundColor' => $red ] ],
                            ],
                            'index' => 0
                        ]
                    ]);
                }

                // Bold + Italic labels in column B when text equals 'Account Summary' or 'SUMMARY'
                foreach (['Account Summary', 'SUMMARY'] as $label) {
                    $requests[] = new \Google\Service\Sheets\Request([
                        'addConditionalFormatRule' => [
                            'rule' => [
                                'ranges' => [[ 'sheetId' => $firstSheetId, 'startRowIndex' => $startData, 'endRowIndex' => $endData, 'startColumnIndex' => 1, 'endColumnIndex' => 2 ]],
                                'booleanRule' => [ 'condition' => [ 'type' => 'TEXT_EQ', 'values' => [[ 'userEnteredValue' => $label ]] ], 'format' => [ 'textFormat' => [ 'bold' => true, 'italic' => true ] ] ],
                            ],
                            'index' => 0
                        ]
                    ]);
                }

                // Auto-resize columns A..J (0..10)
                $requests[] = new \Google\Service\Sheets\Request([
                    'autoResizeDimensions' => [ 'dimensions' => [ 'sheetId' => $firstSheetId, 'dimension' => 'COLUMNS', 'startIndex' => 0, 'endIndex' => 10 ] ]
                ]);

                try {
                    $sheets->spreadsheets->batchUpdate($spreadsheetId, new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                        'requests' => $requests,
                    ]));
                } catch (\Throwable $e) {
                    Log::warning('Sheets formatting skipped: ' . $e->getMessage());
                }
            }

            return response()->json([
                'spreadsheetId' => $spreadsheetId,
                'spreadsheetUrl' => $spreadsheetUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('createRumbleSheet failed: ' . $e->getMessage(), [ 'trace' => $e->getTraceAsString() ]);
            return response()->json([
                'error' => 'google_api_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
