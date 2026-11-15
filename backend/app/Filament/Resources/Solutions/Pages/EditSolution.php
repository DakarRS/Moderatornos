<?php

namespace App\Filament\Resources\Solutions\Pages;

use App\Filament\Resources\Solutions\SolutionResource;
use App\Models\Solution;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSolution extends EditRecord
{
    protected static string $resource = SolutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markVerified')
                ->label('Mark verified today')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('gray')
                ->action(function (Solution $record) {
                    $record->update(['last_verified_on' => today()]);
                    Notification::make()->title('Marked as verified today')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
