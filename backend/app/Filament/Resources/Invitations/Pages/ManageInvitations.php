<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInvitations extends ManageRecords
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New invite')
                ->successNotificationTitle('Invite created. Use "Get link" to copy it.')
                ->using(fn (array $data): Invitation => Invitation::issue($data)),
        ];
    }
}
