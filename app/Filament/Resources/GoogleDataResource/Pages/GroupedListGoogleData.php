<?php

namespace App\Filament\Resources\GoogleDataResource\Pages;

use App\Filament\Resources\GoogleDataResource;
use App\Models\GoogleData;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class GroupedListGoogleData extends Page
{
    protected static string $resource = GoogleDataResource::class;
    protected static string $view = 'filament.resources.google-data-resource.pages.grouped-list-google-data';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadGoogleCsv')
                ->label('Upload Google CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\FileUpload::make('csv_file')
                        ->label('Google CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', '.csv'])
                        ->storeFiles(false)
                        ->preserveFilenames()
                        ->required(),
                    \Filament\Forms\Components\Select::make('report_type')
                        ->label('Report Type')
                        ->options([
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])
                        ->default('weekly')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $today = now();
                            if ($state === 'weekly') {
                                $set('date_preset', 'last_7_days');
                                $set('date_from', $today->copy()->subDays(7)->toDateString());
                                $set('date_to', $today->copy()->subDay()->toDateString());
                            } elseif ($state === 'monthly') {
                                $set('date_preset', 'last_month');
                                $set('date_from', $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString());
                                $set('date_to', $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString());
                            }
                        }),
                    \Filament\Forms\Components\Select::make('date_preset')
                        ->label('Date Preset')
                        ->options([
                            'last_7_days' => 'Last 7 Days (ends yesterday)',
                            'last_month' => 'Last Month',
                            'custom' => 'Custom Range',
                        ])
                        ->default('last_7_days')
                        ->live()
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('date_from')
                        ->label('Date From')
                        ->visible(fn ($get) => $get('date_preset') === 'custom')
                        ->requiredIf('date_preset', 'custom'),
                    \Filament\Forms\Components\DatePicker::make('date_to')
                        ->label('Date To')
                        ->visible(fn ($get) => $get('date_preset') === 'custom')
                        ->requiredIf('date_preset', 'custom'),
                ])
                ->action(function (array $data) {
                    $uploadedFile = $data['csv_file'];
                    $datePreset = $data['date_preset'];
                    $dateFrom = $data['date_from'] ?? null;
                    $dateTo = $data['date_to'] ?? null;
                    $reportType = $data['report_type'] ?? 'weekly';

                    if ($datePreset !== 'custom') {
                        $today = now();
                        switch ($datePreset) {
                            case 'last_7_days':
                                $dateFrom = $today->copy()->subDays(7)->toDateString();
                                $dateTo = $today->copy()->subDay()->toDateString();
                                break;
                            case 'last_month':
                                $dateFrom = $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                                $dateTo = $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                                break;
                        }
                    }

                    $path = $uploadedFile->getRealPath();
                    if (!$path || !file_exists($path)) {
                        throw new \Exception('Unable to read the uploaded file. Please try again.');
                    }

                    $handle = fopen($path, 'r');
                    if (!$handle) {
                        throw new \Exception('Failed to open the uploaded file.');
                    }

                    $header = null;
                    $rows = [];
                    $rangeParsed = false;
                    $lineNo = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        $lineNo++;
                        // Skip empty lines
                        if (count($row) === 1 && trim($row[0]) === '') continue;

                        // Try to parse a range line like "28 July 2025 - 3 August 2025"
                        if (!$rangeParsed && count($row) === 1 && strpos($row[0], '-') !== false) {
                            [$left, $right] = array_map('trim', explode('-', $row[0], 2));
                            try {
                                $leftD = Carbon::parse($left);
                                $rightD = Carbon::parse($right);
                                $dateFrom = $leftD->toDateString();
                                $dateTo = $rightD->toDateString();
                                $rangeParsed = true;
                                continue;
                            } catch (\Throwable $e) {
                                // ignore if unparsable
                            }
                        }

                        // Find the header row that includes "Account name"
                        if ($header === null) {
                            $maybe = array_map('trim', $row);
                            if (in_array('Account name', $maybe) && in_array('Campaign', $maybe)) {
                                $header = $maybe;
                                continue;
                            }
                            continue; // skip preamble lines
                        }

                        $accountIdx = array_search('Account name', $header);
                        $campaignIdx = array_search('Campaign', $header);
                        $costIdx = array_search('Cost', $header);
                        if ($accountIdx === false || $campaignIdx === false || $costIdx === false) {
                            continue; // skip malformed rows
                        }

                        $account = trim($row[$accountIdx] ?? '');
                        $campaign = trim($row[$campaignIdx] ?? '');
                        $costRaw = trim($row[$costIdx] ?? '0');
                        $cost = (float) preg_replace('/[^0-9.\-]/', '', $costRaw);
                        if ($account === '' && $campaign === '' && $cost === 0.0) continue;

                        $rows[] = [
                            'account_name' => $account,
                            'campaign' => $campaign,
                            'cost' => $cost,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'report_type' => $reportType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    if (is_resource($handle)) fclose($handle);

                    if (empty($rows)) {
                        throw new \Exception('No valid data rows found in the CSV.');
                    }

                    GoogleData::insert($rows);

                    Notification::make()
                        ->title('Google CSV Imported')
                        ->body('Your Google CSV has been imported successfully!')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('deleteAllGoogleData')
                ->label('Delete All Google Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->requiresConfirmation()
                ->modalHeading('Delete ALL Google Data?')
                ->modalDescription('This will permanently delete all records in Google Data. This action cannot be undone.')
                ->action(function () {
                    $count = GoogleData::query()->count();
                    GoogleData::query()->delete();
                    Notification::make()
                        ->title('Deleted Google Data')
                        ->body("Deleted {$count} record(s).")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('deleteGoogleDataByDate')
                ->label('Delete Google Data by Upload Date')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\Select::make('upload_date')
                        ->label('Upload Date')
                        ->options(function () {
                            return GoogleData::query()
                                ->selectRaw('DATE(created_at) as d')
                                ->distinct()
                                ->orderBy('d', 'desc')
                                ->pluck('d', 'd')
                                ->toArray();
                        })
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Delete by Upload Date')
                ->modalDescription('This will delete all records imported on the selected date.')
                ->action(function (array $data) {
                    $date = $data['upload_date'];
                    $count = GoogleData::query()->whereDate('created_at', $date)->count();
                    GoogleData::query()->whereDate('created_at', $date)->delete();
                    Notification::make()
                        ->title('Deleted Google Data')
                        ->body("Deleted {$count} record(s) from {$date}.")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('deleteGoogleDataByCategory')
                ->label('Delete Google Data by Date Category')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\Select::make('report_type')
                        ->label('Date Category')
                        ->options([
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Delete by Date Category')
                ->modalDescription('This will delete all records with the selected date category (report type).')
                ->action(function (array $data) {
                    $type = $data['report_type'];
                    $count = GoogleData::query()->where('report_type', $type)->count();
                    GoogleData::query()->where('report_type', $type)->delete();
                    Notification::make()
                        ->title('Deleted Google Data')
                        ->body("Deleted {$count} {$type} record(s).")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getGroupedGoogleData()
    {
        // 1) Fetch only the latest N groups by date range to limit page load
        $groupSummaries = GoogleData::query()
            ->selectRaw('date_from, date_to, report_type, COUNT(*) as c, SUM(cost) as total_cost')
            ->groupBy('date_from', 'date_to', 'report_type')
            ->orderBy('date_to', 'desc')
            ->orderBy('date_from', 'desc')
            ->limit(12)
            ->get();

        if ($groupSummaries->isEmpty()) {
            return collect();
        }

        // 2) Build a where filter for only those groups
        $conditions = $groupSummaries->map(fn ($g) => [
            'date_from' => (string) $g->date_from,
            'date_to' => (string) $g->date_to,
            'report_type' => (string) $g->report_type,
        ])->all();

        $items = GoogleData::query()
            ->select([
                'id', 'account_name', 'campaign', 'cost', 'date_from', 'date_to', 'report_type', 'created_at',
            ])
            ->where(function ($q) use ($conditions) {
                foreach ($conditions as $cond) {
                    $q->orWhere(function ($qq) use ($cond) {
                        $qq->where('date_from', $cond['date_from'])
                           ->where('date_to', $cond['date_to'])
                           ->where('report_type', $cond['report_type']);
                    });
                }
            })
            ->orderBy('date_to', 'desc')
            ->orderBy('date_from', 'desc')
            ->orderBy('account_name', 'asc')
            ->orderBy('campaign', 'asc')
            ->get();

        return $items->groupBy(function ($row) {
            return ($row->date_from ?? '') . '|' . ($row->date_to ?? '') . '|' . ($row->report_type ?? '');
        });
    }

    public function deleteRange(string $rangeKey): void
    {
        [$fromRaw, $toRaw, $typeRaw] = array_pad(explode('|', $rangeKey, 3), 3, null);

        $query = GoogleData::query();
        if (!empty($fromRaw)) {
            $query->whereDate('date_from', $fromRaw);
        }
        if (!empty($toRaw)) {
            $query->whereDate('date_to', $toRaw);
        }
        if (!empty($typeRaw)) {
            $query->where('report_type', $typeRaw);
        }

        $count = (clone $query)->count();
        $query->delete();

        $label = trim(($fromRaw ?? '') . ($toRaw && $toRaw !== $fromRaw ? ' – ' . $toRaw : '')) . ($typeRaw ? " (".ucfirst($typeRaw).")" : '');
        Notification::make()
            ->title('Deleted Google Data')
            ->body("Deleted {$count} record(s) for {$label}.")
            ->success()
            ->send();
    }
}
