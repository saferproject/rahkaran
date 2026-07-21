<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;

readonly class PartyAddressData implements PayloadData
{
    public function __construct(
        public int $RegionalDivisionRef,
        public string $Name,
        public string $Details,
        public int $ID = 0,
        public bool $IsMainAddress = true,
        public string $Details_En = '',
        public string $Phone = '',
        public string $ZipCode = '',
        public string $Email = '',
        public string $Fax = '',
        public string $WebPage = '',
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
