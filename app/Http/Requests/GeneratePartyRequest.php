<?php

namespace App\Http\Requests;

use App\DTO\GeneratePartyData;
use App\DTO\PartyAddressData;
use App\Enums\GeneratePartyGenderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ID' => ['required', 'integer'],
            'Type' => ['required', 'integer'],
            'FirstName' => ['present', 'nullable', 'string'],
            'LastName' => ['present', 'nullable', 'string'],
            'FirstName_EN' => ['present', 'nullable', 'string'],
            'LastName_EN' => ['present', 'nullable', 'string'],
            'CompanyName' => ['present', 'nullable', 'string'],
            'CompanyName_EN' => ['present', 'nullable', 'string'],
            'Alias' => ['present', 'nullable', 'string'],
            'NationalID' => ['present', 'nullable', 'string'],
            'EconomicCode' => ['present', 'nullable', 'string'],
            'Gender' => ['required', Rule::enum(GeneratePartyGenderEnum::class)],
            'PartyAddressData' => ['required', 'array'],
            'PartyAddressData.ID' => ['required', 'integer'],
            'PartyAddressData.IsMainAddress' => ['required', 'boolean'],
            'PartyAddressData.RegionalDivisionRef' => ['required', 'integer'],
            'PartyAddressData.Name' => ['present', 'nullable', 'string'],
            'PartyAddressData.Details' => ['present', 'nullable', 'string'],
            'PartyAddressData.Details_En' => ['present', 'nullable', 'string'],
            'PartyAddressData.Phone' => ['present', 'nullable', 'string'],
            'PartyAddressData.ZipCode' => ['present', 'nullable', 'string'],
            'PartyAddressData.Email' => ['present', 'nullable', 'string'],
            'PartyAddressData.Fax' => ['present', 'nullable', 'string'],
            'PartyAddressData.WebPage' => ['present', 'nullable', 'string'],
        ];
    }

    public function toDto(): GeneratePartyData
    {
        $data = $this->validated();
        $address = $data['PartyAddressData'];

        return new GeneratePartyData(
            ID: $data['ID'],
            Type: $data['Type'],
            FirstName: (string) $data['FirstName'],
            LastName: (string) $data['LastName'],
            FirstName_EN: (string) $data['FirstName_EN'],
            LastName_EN: (string) $data['LastName_EN'],
            CompanyName: (string) $data['CompanyName'],
            CompanyName_EN: (string) $data['CompanyName_EN'],
            Alias: (string) $data['Alias'],
            NationalID: (string) $data['NationalID'],
            EconomicCode: (string) $data['EconomicCode'],
            Gender: GeneratePartyGenderEnum::from((int) $data['Gender']),
            PartyAddressData: new PartyAddressData(
                ID: $address['ID'],
                IsMainAddress: $address['IsMainAddress'],
                RegionalDivisionRef: $address['RegionalDivisionRef'],
                Name: (string) $address['Name'],
                Details: (string) $address['Details'],
                Details_En: (string) $address['Details_En'],
                Phone: (string) $address['Phone'],
                ZipCode: (string) $address['ZipCode'],
                Email: (string) $address['Email'],
                Fax: (string) $address['Fax'],
                WebPage: (string) $address['WebPage'],
            ),
        );
    }
}
