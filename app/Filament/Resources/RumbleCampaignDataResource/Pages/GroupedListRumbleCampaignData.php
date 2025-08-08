<?php

namespace App\Filament\Resources\RumbleCampaignDataResource\Pages;

use App\Filament\Resources\RumbleCampaignDataResource;
use App\Models\RumbleCampaignData;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class GroupedListRumbleCampaignData extends Page
{
    protected static string $resource = RumbleCampaignDataResource::class;

    protected static string $view = 'filament.resources.rumble-campaign-data-resource.pages.grouped-list-rumble-campaign-data';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadCampaignJson')
                ->label('Upload Rumble Campaign JSON')
                ->icon('heroicon-o-arrow-up-tray')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    Forms\Components\FileUpload::make('json_file')
                        ->label('Rumble Campaign JSON File')
                        ->acceptedFileTypes(['application/json', 'text/json', '.json'])
                        ->storeFiles(false)
                        ->preserveFilenames()
                        ->required(),
                    Forms\Components\Select::make('report_type')
                        ->label('Report Type')
                        ->options([
                            'daily' => 'Daily (Yesterday)',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])
                        ->default('daily')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $today = now();
                            if ($state === 'daily') {
                                $set('date_preset', 'yesterday');
                                $set('date_from', $today->copy()->subDay()->toDateString());
                                $set('date_to', $today->copy()->subDay()->toDateString());
                            } elseif ($state === 'weekly') {
                                $set('date_preset', 'last_7_days');
                                $set('date_from', $today->copy()->subDays(7)->toDateString());
                                $set('date_to', $today->copy()->subDay()->toDateString());
                            } elseif ($state === 'monthly') {
                                $set('date_preset', 'last_month');
                                $set('date_from', $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString());
                                $set('date_to', $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString());
                            }
                        }),
                    Forms\Components\Select::make('date_preset')
                        ->label('Date Preset')
                        ->options([
                            'yesterday' => 'Yesterday',
                            'last_7_days' => 'Last 7 Days',
                            'last_month' => 'Last Month',
                            'custom' => 'Custom Range',
                        ])
                        ->live()
                        ->default('yesterday')
                        ->required(),
                    Forms\Components\DatePicker::make('date_from')
                        ->label('Date From')
                        ->visible(fn ($get) => $get('date_preset') === 'custom')
                        ->required(fn ($get) => $get('date_preset') === 'custom'),
                    Forms\Components\DatePicker::make('date_to')
                        ->label('Date To')
                        ->visible(fn ($get) => $get('date_preset') === 'custom')
                        ->required(fn ($get) => $get('date_preset') === 'custom'),
                ])
                ->action(function (array $data) {
                    $uploadedFile = $data['json_file'];
                    $datePreset = $data['date_preset'];
                    $dateFrom = $data['date_from'] ?? null;
                    $dateTo = $data['date_to'] ?? null;
                    $reportType = $data['report_type'] ?? 'daily';

                    if ($datePreset !== 'custom') {
                        $today = now();
                        switch ($datePreset) {
                            case 'yesterday':
                                $dateFrom = $today->copy()->subDay()->toDateString();
                                $dateTo = $dateFrom;
                                break;
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

                    $jsonContent = file_get_contents($path);
                    $json = json_decode($jsonContent, true);
                    if (!is_array($json) || !isset($json['header'], $json['body']) || !is_array($json['header']) || !is_array($json['body'])) {
                        throw new \Exception('Invalid JSON structure. Expected keys: header, body.');
                    }

                    // Normalize headers to find indices more reliably
                    $normalize = function ($value) {
                        $value = strtolower(trim((string) $value));
                        $value = str_replace(['\\', '/', '-', '_'], '', $value);
                        $value = preg_replace('/\s+/', '', $value);
                        return $value;
                    };
                    $normalizedHeader = array_map($normalize, $json['header']);

                    $nameIdx = array_search('name', $normalizedHeader);
                    $cpmIdx = array_search('cpm', $normalizedHeader);
                    $usedDailyIdx = array_search('useddailylimit', $normalizedHeader);

                    if ($nameIdx === false || $cpmIdx === false || $usedDailyIdx === false) {
                        throw new \Exception('JSON must contain Name, CPM, and Used / Daily Limit columns.');
                    }

                    $rows = [];

                    $extractDailyLimit = function ($value) {
                        if ($value === null) return null;
                        $val = trim((string) $value);
                        if ($val === '') return null;
                        // Expect formats like "$7.03 / $100" or "$1,411.21 / Unlimited"
                        if (stripos($val, 'unlimited') !== false) {
                            return null;
                        }
                        if (strpos($val, '/') !== false) {
                            [$left, $right] = array_pad(explode('/', $val, 2), 2, null);
                            $right = trim((string) $right);
                            $digits = preg_replace('/[^0-9]/', '', $right);
                            return $digits !== '' ? (int) $digits : null;
                        }
                        return null;
                    };

                    $extractCpm = function ($value): float {
                        $val = trim((string) $value);
                        if ($val === '') return 0.0;
                        // Capture the first numeric token (supports comma or dot)
                        if (preg_match('/([0-9]+(?:[\.,][0-9]+)?)/', $val, $m)) {
                            $num = $m[1];
                            // Remove thousand separators, normalize decimal to dot
                            $num = str_replace(',', '', $num);
                            return (float) $num;
                        }
                        return 0.0;
                    };

                    foreach ($json['body'] as $row) {
                        if (!is_array($row)) continue;
                        $name = (string) ($row[$nameIdx] ?? '');
                        // Remove numeric prefixes like "123 - ", "[123] ", "123.", etc.
                        $name = preg_replace('/^\s*(?:\[\d+\]|\d+)\s*[-–.:]*\s*/', '', $name);
                        $cpm = $extractCpm($row[$cpmIdx] ?? null);
                        $dailyLimit = $extractDailyLimit($row[$usedDailyIdx] ?? null);

                        if ($name === '') {
                            continue;
                        }

                        $rows[] = [
                            'name' => $name,
                            'cpm' => $cpm,
                            'daily_limit' => $dailyLimit,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'report_type' => $reportType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($rows)) {
                        RumbleCampaignData::insert($rows);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Rumble Campaign JSON Imported')
                        ->body('Your campaign JSON has been imported successfully!')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteAllCampaignData')
                ->label('Delete All Campaign Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->requiresConfirmation()
                ->modalHeading('Delete ALL Rumble Campaign Data?')
                ->modalDescription('This will permanently delete all records in Rumble Campaign Data. This action cannot be undone.')
                ->action(function () {
                    $count = RumbleCampaignData::query()->count();
                    RumbleCampaignData::query()->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Deleted Rumble Campaign Data')
                        ->body("Deleted {$count} record(s).")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteCampaignDataByDate')
                ->label('Delete Campaign Data by Upload Date')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    Forms\Components\Select::make('upload_date')
                        ->label('Upload Date')
                        ->options(function () {
                            return RumbleCampaignData::query()
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
                    $count = RumbleCampaignData::query()->whereDate('created_at', $date)->count();
                    RumbleCampaignData::query()->whereDate('created_at', $date)->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Deleted Rumble Campaign Data')
                        ->body("Deleted {$count} record(s) from {$date}.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteCampaignDataByCategory')
                ->label('Delete Campaign Data by Date Category')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    Forms\Components\Select::make('report_type')
                        ->label('Date Category')
                        ->options([
                            'daily' => 'Daily',
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
                    $count = RumbleCampaignData::query()->where('report_type', $type)->count();
                    RumbleCampaignData::query()->where('report_type', $type)->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Deleted Rumble Campaign Data')
                        ->body("Deleted {$count} {$type} record(s).")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getGroupedCampaignData()
    {
        return RumbleCampaignData::query()
            ->select([
                'id',
                'name',
                'cpm',
                'daily_limit',
                'date_from',
                'date_to',
                'report_type',
                'created_at',
            ])
            ->orderBy('date_to', 'desc')
            ->orderBy('date_from', 'desc')
            ->get()
            ->groupBy(function ($row) {
                // Group by exact report date range key: from|to
                return ($row->date_from ?? '') . '|' . ($row->date_to ?? '');
            });
    }

    public function deleteRange(string $rangeKey): void
    {
        [$fromRaw, $toRaw] = array_pad(explode('|', $rangeKey, 2), 2, null);

        $query = RumbleCampaignData::query();
        if (!empty($fromRaw)) {
            $query->whereDate('date_from', $fromRaw);
        }
        if (!empty($toRaw)) {
            $query->whereDate('date_to', $toRaw);
        }

        $count = (clone $query)->count();
        $query->delete();

        $label = trim(($fromRaw ?? '') . ($toRaw && $toRaw !== $fromRaw ? ' – ' . $toRaw : ''));
        Notification::make()
            ->title('Deleted Rumble Campaign Data')
            ->body("Deleted {$count} record(s) for {$label}.")
            ->success()
            ->send();
    }
}
