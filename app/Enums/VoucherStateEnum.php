<?php

namespace App\Enums;

enum VoucherStateEnum: int
{
    use Enumable;

    case None = 0;
    case WithNoRevision = 1;
    case Note = 2;

    public function label(): \Illuminate\Foundation\Application|array|string|\Illuminate\Contracts\Translation\Translator|\Illuminate\Contracts\Foundation\Application|null
    {
        return match ($this) {
            self::None => __('labels.voucher_state_enum.none'),
            self::WithNoRevision => __('labels.voucher_state_enum.with_no_revision'),
            self::Note => __('labels.voucher_state_enum.note'),
        };
    }
}
