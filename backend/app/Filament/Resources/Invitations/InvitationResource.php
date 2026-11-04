<?php

namespace App\Filament\Resources\Invitations;

use App\Enums\UserRole;
use App\Filament\Resources\Invitations\Pages\ManageInvitations;
use App\Models\Invitation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'invite';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Who is this for?')
                    ->helperText('Optional, just so you can tell invites apart.')
                    ->maxLength(255),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::Editor)
                    ->required()
                    ->native(false),
                Select::make('expires_in_days')
                    ->label('Expires after')
                    ->options([1 => '1 day', 7 => '7 days', 30 => '30 days'])
                    ->default(7)
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('label')->label('For')->placeholder('-')->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->name)
                    ->color(fn (UserRole $state): string => $state === UserRole::Admin ? 'primary' : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Invitation $record): string => match (true) {
                        $record->used_at !== null => 'Used',
                        $record->isPending() => 'Pending',
                        default => 'Expired',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'success',
                        'Used' => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('usedBy.username')->label('Used by')->placeholder('-'),
                TextColumn::make('expires_at')->label('Expires')->date()->sortable(),
                TextColumn::make('creator.username')->label('Created by')->placeholder('-'),
            ])
            ->recordActions([
                Action::make('link')
                    ->label('Get link')
                    ->icon(Heroicon::OutlinedLink)
                    ->visible(fn (Invitation $record): bool => $record->isPending())
                    ->modalHeading('Invite link')
                    ->modalDescription('Works once. Anyone with this link can register, so only send it to the right person.')
                    ->fillForm(fn (Invitation $record): array => ['link' => $record->link])
                    ->schema([TextInput::make('link')->hiddenLabel()->readOnly()])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                DeleteAction::make()
                    ->label('Revoke')
                    ->visible(fn (Invitation $record): bool => $record->used_at === null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInvitations::route('/'),
        ];
    }
}
