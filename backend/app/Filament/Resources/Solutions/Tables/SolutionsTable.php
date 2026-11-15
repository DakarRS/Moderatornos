<?php

namespace App\Filament\Resources\Solutions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SolutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('edition')->badge(),
                TextColumn::make('tags.name')->label('Tags')->badge()->toggleable(),
                TextColumn::make('last_verified_on')->label('Verified')->date()->sortable()->toggleable(),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Published'),
                SelectFilter::make('tags')->relationship('tags', 'name')->preload(),
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
