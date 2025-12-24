<?php

namespace App\Enums;

enum ItemCondition: int
{
    case Good = 1;
    case NoNoticeableDamage = 2;
    case SomeDamage = 3;
    case Bad = 4;

    public function label(): string
    {
        return match ($this) {
            self::Good => '良好',
            self::NoNoticeableDamage => '目立った傷や汚れなし',
            self::SomeDamage => 'やや傷や汚れあり',
            self::Bad => '状態が悪い',
        };
    }
}
