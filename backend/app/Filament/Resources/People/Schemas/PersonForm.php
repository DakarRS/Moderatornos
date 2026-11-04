<?php

namespace App\Filament\Resources\People\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\Intl\Countries;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label('Photo')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        TextInput::make('display_name')->required()->maxLength(255)->columnSpanFull(),
                        Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('country_code')
                            ->label('Country')
                            ->options(Countries::getNames())
                            ->searchable(),
                        // The frontend renders this as a link, so only allow web schemes.
                        TextInput::make('website')->url()->rules(['url:http,https'])->maxLength(255),
                        self::dateField('joined_on', 'Joined'),
                        self::dateField('left_on', 'Left')
                            // The comparison only applies once both dates are filled in.
                            ->afterOrEqual(fn (Get $get): ?string => filled($get('joined_on')) ? 'joined_on' : null),
                        Textarea::make('bio')->label('Description')->rows(5)->maxLength(5000)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function dateField(string $name, string $label): DatePicker
    {
        return DatePicker::make($name)
            ->label($label)
            ->native(false)
            ->displayFormat('j M Y')
            ->closeOnDateSelection()
            // The clear button's visibility is decided on the server, so it needs to hear about changes.
            ->live()
            ->prefixIcon(Heroicon::OutlinedCalendarDays)
            // These are optional, and the calendar itself has no visible way to empty them.
            ->suffixAction(
                Action::make('clear')
                    ->label('Clear date')
                    ->icon(Heroicon::XMark)
                    ->hidden(fn (Get $get): bool => blank($get($name)))
                    ->action(fn (Set $set) => $set($name, null)),
            );
    }
}
