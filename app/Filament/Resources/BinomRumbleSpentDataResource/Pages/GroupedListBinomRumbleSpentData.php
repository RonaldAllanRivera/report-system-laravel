<?php

namespace App\Filament\Resources\BinomRumbleSpentDataResource\Pages;

use App\Filament\Resources\BinomRumbleSpentDataResource;
use App\Models\BinomRumbleSpentData;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class GroupedListBinomRumbleSpentData extends Page
{
    protected static string $resource = BinomRumbleSpentDataResource::class;
    protected static string $view = 'filament.resources.binom-rumble-spent-data-resource.pages.grouped-list-binom-rumble-spent-data';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadBinomCsv')
                ->label('Upload Binom Rumble Spent CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\FileUpload::make('csv_file')
                        ->label('Binom Rumble Spent CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', '.csv'])
                        ->storeFiles(false)
                        ->preserveFilenames()
                        ->required(),
                    \Filament\Forms\Components\Select::make('report_type')
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
                    \Filament\Forms\Components\Select::make('date_preset')
                        ->label('Date Preset')
                        ->options([
                            'yesterday' => 'Yesterday',
                            'last_7_days' => 'Last 7 Days',
                            'last_month' => 'Last Month',
                            'custom' => 'Custom Range',
                        ])
                        ->default('yesterday')
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

                    $handle = fopen($path, 'r');
                    // Semicolon delimited, quoted entries
                    $header = fgetcsv($handle, 0, ';', '"');
                    if (!$header) {
                        fclose($handle);
                        throw new \Exception('CSV header not found.');
                    }

                    // Normalize header: strip UTF-8 BOM, trim quotes/whitespace, lowercase
                    $normalizedHeader = array_map(function ($h) {
                        $h = (string) $h;
                        // remove UTF-8 BOM if present
                        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                        $h = trim($h, " \t\n\r\0\x0B\"'{}");
                        return strtolower($h);
                    }, $header);

                    $nameIdx = array_search('name', $normalizedHeader);
                    $leadsIdx = array_search('leads', $normalizedHeader);
                    $revenueIdx = array_search('revenue', $normalizedHeader);

                    if ($nameIdx === false || $leadsIdx === false || $revenueIdx === false) {
                        fclose($handle);
                        throw new \Exception('CSV must contain Name, Leads, and Revenue columns.');
                    }

                    $rows = [];
                    while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
                        $name = $row[$nameIdx] ?? null;
                        $leadsRaw = $row[$leadsIdx] ?? '0';
                        $revenueRaw = $row[$revenueIdx] ?? '0';

                        // numeric cleanup
                        $leads = (int) preg_replace('/[^0-9]/', '', (string) $leadsRaw);
                        $revenue = (float) preg_replace('/[^0-9.]/', '', (string) $revenueRaw);

                        // skip zero revenue
                        if ($revenue <= 0) {
                            continue;
                        }

                        $rows[] = [
                            'name' => $name,
                            'leads' => $leads,
                            'revenue' => $revenue,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'report_type' => $reportType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    fclose($handle);

                    if (!empty($rows)) {
                        BinomRumbleSpentData::insert($rows);
                    }

                    Notification::make()
                        ->title('Binom CSV Imported')
                        ->body('Your Binom Rumble Spent Data CSV has been imported successfully!')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteAllBinomRumbleSpentData')
                ->label('Delete All Binom Spent Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->requiresConfirmation()
                ->modalHeading('Delete ALL Binom Rumble Spent Data?')
                ->modalDescription('This will permanently delete all records in Binom Rumble Spent Data. This action cannot be undone.')
                ->action(function () {
                    $count = BinomRumbleSpentData::query()->count();
                    BinomRumbleSpentData::query()->delete();
                    Notification::make()
                        ->title('Deleted Binom Rumble Spent Data')
                        ->body("Deleted {$count} record(s).")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteBinomByDate')
                ->label('Delete Binom Spent Data by Upload Date')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\Select::make('upload_date')
                        ->label('Upload Date')
                        ->options(function () {
                            return BinomRumbleSpentData::query()
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
                    $count = BinomRumbleSpentData::query()->whereDate('created_at', $date)->count();
                    BinomRumbleSpentData::query()->whereDate('created_at', $date)->delete();
                    Notification::make()
                        ->title('Deleted Binom Rumble Spent Data')
                        ->body("Deleted {$count} record(s) from {$date}.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteBinomByCategory')
                ->label('Delete Binom Spent Data by Date Category')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\Select::make('report_type')
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
                    $count = BinomRumbleSpentData::query()->where('report_type', $type)->count();
                    BinomRumbleSpentData::query()->where('report_type', $type)->delete();
                    Notification::make()
                        ->title('Deleted Binom Rumble Spent Data')
                        ->body("Deleted {$count} {$type} record(s).")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getGroupedBinomRumbleSpentData()
    {
        return BinomRumbleSpentData::query()
            ->select([
                'id',
                'name',
                'leads',
                'revenue',
                'date_from',
                'date_to',
                'report_type',
                'created_at',
            ])
            ->orderBy('date_to', 'desc')
            ->orderBy('date_from', 'desc')
            ->get()
            ->groupBy(function ($row) {
                return ($row->date_from ?? '') . '|' . ($row->date_to ?? '');
            });
    }

    public function deleteRange(string $rangeKey): void
    {
        [$fromRaw, $toRaw] = array_pad(explode('|', $rangeKey, 2), 2, null);

        $query = BinomRumbleSpentData::query();
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
            ->title('Deleted Binom Rumble Spent Data')
            ->body("Deleted {$count} record(s) for {$label}.")
            ->success()
            ->send();
    }
}
