<?php

namespace App\Enums;

enum GeneratePartyGenderEnum: int
{
    use Enumable;
    case Male = 1;
    case Female = 2;

    public function label(): \Illuminate\Foundation\Application|array|string|\Illuminate\Contracts\Translation\Translator|\Illuminate\Contracts\Foundation\Application|null
    {
        return match ($this) {
            self::Male => __('labels.generate_party_gender_enum.male'),
            self::Female => __('labels.generate_party_gender_enum.female'),
        };
    }
}
