<?php

namespace App\Enums;

use Illuminate\Support\Arr;

trait Enumable
{
    public function details()
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }

    public static function activeCases(): array
    {
        return self::cases();
    }

    public static function fromActive($value)
    {
        $activeCases = self::activeCases();

        return collect($activeCases)->where('value', $value)->firstOrFail();
    }

    public static function tryFromActive($value)
    {
        $activeCases = self::activeCases();

        return collect($activeCases)->where('value', $value)->first();
    }

    public static function names(): array
    {
        return array_column(self::activeCases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::activeCases(), 'value');
    }

    public static function all(): array
    {
        $activeCases = static::activeCases();

        return Arr::map($activeCases, fn($item) => $item->details());
    }
}
