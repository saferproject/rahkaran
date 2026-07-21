<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;

readonly class RegisterDLData implements PayloadData
{
    public function __construct(
        public string $Code,
        public int $DLTypeRef,
        public string $Title,
        public string $Description = '',
        public int $ID = 0,
        public int $ReferenceID = 0,
        public string $Title_En = '',
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
