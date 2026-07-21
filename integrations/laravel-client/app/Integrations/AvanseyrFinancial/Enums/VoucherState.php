<?php

namespace App\Integrations\AvanseyrFinancial\Enums;

enum VoucherState: int
{
    case None = 0;
    case WithNoRevision = 1;
    case Note = 2;
}
