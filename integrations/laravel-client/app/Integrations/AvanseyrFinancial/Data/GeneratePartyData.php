<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;
use App\Integrations\AvanseyrFinancial\Enums\PartyGender;

readonly class GeneratePartyData implements PayloadData
{
    public function __construct(
        public int $Type,
        public PartyGender $Gender,
        public PartyAddressData $PartyAddressData,
        public int $ID = 0,
        public string $FirstName = '',
        public string $LastName = '',
        public string $FirstName_EN = '',
        public string $LastName_EN = '',
        public string $CompanyName = '',
        public string $CompanyName_EN = '',
        public string $Alias = '',
        public string $NationalID = '',
        public string $EconomicCode = '',
    ) {}

    public function toArray(): array
    {
        return [
            'ID' => $this->ID,
            'Type' => $this->Type,
            'FirstName' => $this->FirstName,
            'LastName' => $this->LastName,
            'FirstName_EN' => $this->FirstName_EN,
            'LastName_EN' => $this->LastName_EN,
            'CompanyName' => $this->CompanyName,
            'CompanyName_EN' => $this->CompanyName_EN,
            'Alias' => $this->Alias,
            'NationalID' => $this->NationalID,
            'EconomicCode' => $this->EconomicCode,
            'Gender' => $this->Gender->value,
            'PartyAddressData' => $this->PartyAddressData->toArray(),
        ];
    }
}
