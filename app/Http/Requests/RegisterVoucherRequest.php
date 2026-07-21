<?php

namespace App\Http\Requests;

use App\DTO\RegisterVoucherData;
use App\DTO\VoucherItemData;
use App\Enums\VoucherStateEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'BranchRef' => ['required', 'integer'],
            'Date' => ['present', 'nullable', 'string'],
            'Description' => ['present', 'nullable', 'string'],
            'Description_En' => ['present', 'nullable', 'string'],
            'FiscalYearRef' => ['required', 'integer'],
            'LedgerRef' => ['required', 'integer'],
            'VoucherTypeRef' => ['required', 'integer'],
            'VoucherTypeOwnerSystem' => ['present', 'nullable', 'string'],
            'VoucherTypeCode' => ['required', 'integer'],
            'Number' => ['required', 'integer'],
            'AuxiliaryNumber' => ['present', 'nullable', 'string'],
            'IsCurrentBased' => ['required', 'boolean'],
            'State' => ['required', Rule::enum(VoucherStateEnum::class)],
            'IsExternal' => ['required', 'boolean'],
            'Creator' => ['required', 'integer'],
            'CreatorName' => ['present', 'nullable', 'string'],
            'StateTitle' => ['present', 'nullable', 'string'],
            'VoucherItemData' => ['required', 'array'],
            'VoucherItemData.*.VoucherItemID' => ['required', 'integer'],
            'VoucherItemData.*.SLRef' => ['required', 'integer'],
            'VoucherItemData.*.Debit' => ['required', 'numeric'],
            'VoucherItemData.*.Credit' => ['required', 'numeric'],
            'VoucherItemData.*.CurrencyAmount' => ['required', 'numeric'],
            'VoucherItemData.*.BaseCurrencyAmount' => ['required', 'numeric'],
            'VoucherItemData.*.CurrencyCredit' => ['required', 'numeric'],
            'VoucherItemData.*.CurrencyDebit' => ['required', 'numeric'],
            'VoucherItemData.*.CurrencyRef' => ['required', 'integer'],
            'VoucherItemData.*.BaseCurrencyRef' => ['required', 'integer'],
            'VoucherItemData.*.OperationalCurrencyExchangeRate' => ['required', 'numeric'],
            'VoucherItemData.*.OperationalCurrencyExchangeRateRef' => ['required', 'integer'],
            'VoucherItemData.*.BaseCurrencyExchangeRate' => ['required', 'numeric'],
            'VoucherItemData.*.BaseCurrencyExchangeRateRef' => ['required', 'integer'],
            'VoucherItemData.*.DL' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.DLTypeRef' => ['required', 'integer'],
            'VoucherItemData.*.Description' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.Description_En' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.FollowUpDate' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.FollowUpNumber' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.RowNumber' => ['required', 'integer'],
            'VoucherItemData.*.SLCode' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.ExtraData' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.TaxAccountType' => ['required', 'integer'],
            'VoucherItemData.*.TransactionType' => ['required', 'integer'],
            'VoucherItemData.*.TaxStateType' => ['required', 'integer'],
            'VoucherItemData.*.PurchaseOrSale' => ['required', 'integer'],
            'VoucherItemData.*.ItemOrService' => ['required', 'integer'],
            'VoucherItemData.*.PartyRef' => ['required', 'integer'],
            'VoucherItemData.*.TaxAmount' => ['required', 'numeric'],
            'VoucherItemData.*.TollAmount' => ['required', 'numeric'],
            'VoucherItemData.*.SLTitle' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.IsSLTraceable' => ['required', 'boolean'],
            'VoucherItemData.*.OperationalCurrencyPrecision' => ['required', 'integer'],
            'VoucherItemData.*.DLLevelTitle' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.CurrencyPrecision' => ['required', 'integer'],
            'VoucherItemData.*.CurrencyTitle' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.BaseCurrencyPrecision' => ['required', 'integer'],
            'VoucherItemData.*.BaseCurrencyTitle' => ['present', 'nullable', 'string'],
            'VoucherItemData.*.NumberOfSLDLLevels' => ['required', 'integer'],
            'VoucherItemData.*.Quantity' => ['required', 'integer'],
        ];
    }

    public function toDto(): RegisterVoucherData
    {
        $data = $this->validated();

        return new RegisterVoucherData(
            BranchRef: $data['BranchRef'],
            Date: (string) $data['Date'],
            Description: (string) $data['Description'],
            Description_En: (string) $data['Description_En'],
            FiscalYearRef: $data['FiscalYearRef'],
            LedgerRef: $data['LedgerRef'],
            VoucherTypeRef: $data['VoucherTypeRef'],
            VoucherTypeOwnerSystem: (string) $data['VoucherTypeOwnerSystem'],
            VoucherTypeCode: $data['VoucherTypeCode'],
            Number: $data['Number'],
            AuxiliaryNumber: (string) $data['AuxiliaryNumber'],
            IsCurrentBased: $data['IsCurrentBased'],
            State: VoucherStateEnum::from((int) $data['State']),
            IsExternal: $data['IsExternal'],
            Creator: $data['Creator'],
            CreatorName: (string) $data['CreatorName'],
            StateTitle: (string) $data['StateTitle'],
            VoucherItemData: array_map(
                fn (array $item): VoucherItemData => new VoucherItemData(...array_map(
                    fn (mixed $value): mixed => $value ?? '',
                    $item,
                )),
                $data['VoucherItemData'],
            ),
        );
    }
}
