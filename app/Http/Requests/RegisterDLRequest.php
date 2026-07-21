<?php

namespace App\Http\Requests;

use App\DTO\RegisterDLData;
use Illuminate\Foundation\Http\FormRequest;

class RegisterDLRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'Code' => ['present', 'nullable', 'string'],
            'DLTypeRef' => ['required', 'integer'],
            'Description' => ['present', 'nullable', 'string'],
            'ID' => ['required', 'integer'],
            'ReferenceID' => ['required', 'integer'],
            'Title' => ['present', 'nullable', 'string'],
            'Title_En' => ['present', 'nullable', 'string'],
        ];
    }

    public function toDto(): RegisterDLData
    {
        $data = $this->validated();

        return new RegisterDLData(
            Code: (string) $data['Code'],
            DLTypeRef: $data['DLTypeRef'],
            Description: (string) $data['Description'],
            ID: $data['ID'],
            ReferenceID: $data['ReferenceID'],
            Title: (string) $data['Title'],
            Title_En: (string) $data['Title_En'],
        );
    }
}
