<?php

namespace App\DTO;

class PartyAddressData
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $ID,
        public bool $IsMainAddress,
        public int $RegionalDivisionRef,
        public string $Name,
        public string $Details,
        public string $Details_En,
        public string $Phone,
        public string $ZipCode,
        public string $Email,
        public string $Fax,
        public string $WebPage,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
