<?php

namespace App\Filament\Resources\RumbleDataResource\Pages;

use App\Filament\Resources\RumbleDataResource;
use App\Models\RumbleData;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class GroupedListRumbleData extends Page
{
    protected static string $resource = RumbleDataResource::class;
    protected static string $view = 'filament.resources.rumble-data-resource.pages.grouped-list-rumble-data';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadRumbleCsv')
                ->label('Upload Rumble CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\FileUpload::make('csv_file')
                        ->label('Rumble CSV File')
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
                        ->live()
                        ->default('yesterday')
                        ->required()
                        ->extraAttributes(['class' => 'hover:text-gray-900']),
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

                    // Handle date presets
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

                    // Parse and import CSV
                    $path = $uploadedFile->getRealPath();
                    
                    if (!$path) {
                        throw new \Exception('The uploaded file is not valid. Please try again.');
                    }
                    
                    if (!file_exists($path)) {
                        throw new \Exception('Unable to read the uploaded file. Please try again.');
                    }
                    
                    $handle = fopen($path, 'r');
                    $header = fgetcsv($handle);
                    $campaignIdx = array_search('Campaign', $header);
                    $spendIdx = array_search('Spend', $header);
                    $cpmIdx = array_search('CPM', $header);

                    if ($campaignIdx === false || $spendIdx === false || $cpmIdx === false) {
                        throw new \Exception('CSV must contain Campaign, Spend, and CPM columns.');
                    }

                    $rows = [];
                    while (($row = fgetcsv($handle)) !== false) {
                        // Remove the [12345] prefix from campaign names
                        $campaignName = preg_replace('/^\[\d+\]\s*/', '', $row[$campaignIdx]);
                        
                        $rows[] = [
                            'campaign' => $campaignName,
                            'spend' => $row[$spendIdx],
                            'cpm' => $row[$cpmIdx],
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'report_type' => $reportType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    fclose($handle);

                    \App\Models\RumbleData::insert($rows);

                    \Filament\Notifications\Notification::make()
                        ->title('Rumble CSV Imported')
                        ->body('Your Rumble CSV has been imported successfully!')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteAllRumbleData')
                ->label('Delete All Rumble Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->requiresConfirmation()
                ->modalHeading('Delete ALL Rumble Data?')
                ->modalDescription('This will permanently delete all records in Rumble Data. This action cannot be undone.')
                ->action(function () {
                    $count = RumbleData::query()->count();
                    RumbleData::query()->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Deleted Rumble Data')
                        ->body("Deleted {$count} record(s).")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteRumbleDataByDate')
                ->label('Delete Rumble Data by Upload Date')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\Select::make('upload_date')
                        ->label('Upload Date')
                        ->options(function () {
                            return RumbleData::query()
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
                    $count = RumbleData::query()->whereDate('created_at', $date)->count();
                    RumbleData::query()->whereDate('created_at', $date)->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Deleted Rumble Data')
                        ->body("Deleted {$count} record(s) from {$date}.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteRumbleDataByCategory')
                ->label('Delete Rumble Data by Date Category')
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
                    $count = RumbleData::query()->where('report_type', $type)->count();
                    RumbleData::query()->where('report_type', $type)->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Deleted Rumble Data')
                        ->body("Deleted {$count} {$type} record(s).")
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function getGroupedRumbleData()
    {
        return RumbleData::query()
            ->select([
                'id',
                'campaign',
                'spend',
                'cpm',
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

        $query = RumbleData::query();
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
            ->title('Deleted Rumble Data')
            ->body("Deleted {$count} record(s) for {$label}.")
            ->success()
            ->send();
    }
}
