<?php

namespace App\Filament\Resources\Solutions\Schemas;

use App\Enums\Edition;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SolutionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            // Only on create: after that the slug stays put unless someone edits it.
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                            ->helperText('Used in links. Leave empty to build it from the title. Changing it breaks links already shared.'),
                        MarkdownEditor::make('symptoms')->helperText('What the player sees.'),
                        MarkdownEditor::make('cause')->helperText('Why it happens, if known.'),
                        MarkdownEditor::make('fix')->required(),
                    ]),
                Section::make()
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_published')->label('Published')->helperText('Drafts are only visible here.'),
                        Select::make('edition')->options(Edition::class)->required(),
                        TextInput::make('versions')->placeholder('1.20–1.21')->maxLength(255),
                        Select::make('tags')->relationship('tags', 'name')->multiple()->preload(),
                        Repeater::make('signatures')
                            ->relationship()
                            ->schema([TextInput::make('pattern')->hiddenLabel()->required()->maxLength(500)])
                            ->addActionLabel('Add signature')
                            ->defaultItems(0)
                            ->helperText('Text from a log that points to this problem, matched without case.'),
                    ]),
            ]);
    }
}
