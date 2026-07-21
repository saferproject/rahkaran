<?php

namespace App\DTO;

use App\Enums\GeneratePartyGenderEnum;

class GeneratePartyData
{
    public function __construct(
        public int $ID,
        public int $Type,
        public string $FirstName,
        public string $LastName,
        public string $FirstName_EN,
        public string $LastName_EN,
        public string $CompanyName,
        public string $CompanyName_EN,
        public string $Alias,
        public string $NationalID,
        public string $EconomicCode,
        public GeneratePartyGenderEnum $Gender,
        public PartyAddressData $PartyAddressData,
    ) {}

    /**
     * @return array{
     *      ID: int,
     *      Type: int,
     *      FirstName: string,
     *      LastName: string,
     *      FirstName_EN: string,
     *      LastName_EN: string,
     *      CompanyName: string,
     *      CompanyName_EN: string,
     *      Alias: string,
     *      NationalID: string,
     *      EconomicCode: string,
     *      Gender: GeneratePartyGender,
     *      PartyAddressData: PartyAddressData
     *  }
     */
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
