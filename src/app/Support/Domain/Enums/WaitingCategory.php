<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum WaitingCategory: string
{
    case PRE_ASSISTANCE = 'pre_assistance';
    case DURING_ASSISTANCE = 'during_assistance';
    case POST_ASSISTANCE = 'post_assistance';

    public function label(): string
    {
        return match($this) {
            self::PRE_ASSISTANCE => 'Pre-asistencia',
            self::DURING_ASSISTANCE => 'Durante asistencia',
            self::POST_ASSISTANCE => 'Post-asistencia',
        };
    }
}
