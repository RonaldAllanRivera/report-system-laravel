<?php

namespace App\Filament\Resources\RumbleDataResource\Pages;

use App\Filament\Resources\RumbleDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRumbleData extends ListRecords
{
    protected static string $resource = RumbleDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('uploadRumbleCsv')
                ->label('Upload Rumble CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('csv_file')
                        ->label('Rumble CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', '.csv'])
                        ->storeFiles(false) // Prevent permanent storage
                        ->preserveFilenames() // Keep the original filename
                        ->required(),
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
        ];
    }
}
