<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Edition: string implements HasLabel
{
    case Java = 'java';
    case Bedrock = 'bedrock';
    case Both = 'both';

    public function getLabel(): string
    {
        return match ($this) {
            self::Java => 'Java',
            self::Bedrock => 'Bedrock',
            self::Both => 'Java and Bedrock',
        };
    }
}
