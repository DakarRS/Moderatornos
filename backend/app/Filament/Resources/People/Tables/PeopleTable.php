<?php

namespace App\Filament\Resources\People\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('joined_on')
            ->columns([
                ImageColumn::make('avatar_path')->label('')->disk('public')->circular(),
                TextColumn::make('display_name')
                    ->label('Name')
                    // @phpstan-ignore-next-line Filament types this builder without its model.
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderedByName($direction))
                    // @phpstan-ignore-next-line Filament types this builder without its model.
                    ->searchable(query: fn (Builder $query, string $search) => $query->search($search)),
                TextColumn::make('categories.name')->label('Categories')->badge()->toggleable(),
                TextColumn::make('country_name')->label('Country')->sortable(['country_code'])->toggleable(),
                TextColumn::make('joined_on')
                    ->label('Joined')
                    ->date()
                    // @phpstan-ignore-next-line Filament types this builder without its model.
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderedByJoined($direction))
                    ->toggleable(),
                TextColumn::make('left_on')->label('Left')->date()->sortable()->toggleable(),
                TextColumn::make('website')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categories')->relationship('categories', 'name')->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
