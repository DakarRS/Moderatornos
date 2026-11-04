<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->maxLength(50)
                    ->regex(User::USERNAME_PATTERN)
                    ->unique(ignoreRecord: true)
                    ->helperText('Used to log in. Lowercase letters, numbers, dots, dashes and underscores.'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::Editor)
                    ->required()
                    ->native(false)
                    // A disabled field isn't saved, so at least one admin always remains.
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->helperText(fn (?User $record): ?string => $record?->is(auth()->user()) ? "You can't change your own role." : null),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->rule(User::passwordRule())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    // Left blank when editing, so the current password stays.
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password.' : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('username')
            ->columns([
                TextColumn::make('username')->searchable()->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->name)
                    ->color(fn (UserRole $state): string => $state === UserRole::Admin ? 'primary' : 'gray')
                    ->sortable(),
                TextColumn::make('created_at')->label('Added')->date()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                // UserPolicy refuses deleting yourself, and Filament hides the button to match.
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
