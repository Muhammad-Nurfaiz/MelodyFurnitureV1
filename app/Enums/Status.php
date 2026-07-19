<?php

namespace App\Enums;

enum Status: int
{
    case INACTIVE = 0;

    case ACTIVE = 1;

    /**
     * Label Indonesia
     */
    public function label(): string
    {
        return match ($this) {

            self::ACTIVE => 'Aktif',

            self::INACTIVE => 'Nonaktif',

        };
    }

    /**
     * Badge Color
     */
    public function color(): string
    {
        return match ($this) {

            self::ACTIVE => 'green',

            self::INACTIVE => 'red',

        };
    }

    /**
     * Icon Heroicons
     */
    public function icon(): string
    {
        return match ($this) {

            self::ACTIVE => 'check-circle',

            self::INACTIVE => 'x-circle',

        };
    }

    /**
     * Option Select
     */
    public static function options(): array
    {
        return array_map(

            fn(self $status) => [

                'value' => $status->value,

                'label' => $status->label(),

            ],

            self::cases()

        );
    }
}