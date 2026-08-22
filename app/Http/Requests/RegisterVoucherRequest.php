<?php

namespace App\Http\Requests;

use App\DTO\RegisterVoucherData;
use App\DTO\VoucherItemData;
use App\Enums\VoucherStateEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterVoucherRequest extends FormRequest
{
    /**
     * Voucher fields Rahkaran expects in the payload but that the caller may
     * omit. They are filled with a neutral value instead of being rejected by
     * a `required` rule.
     *
     * @var array<string, string|bool>
     */
    private const VOUCHER_DEFAULTS = [
        'Date' => '',
        'Description' => '',
        'Description_En' => '',
        'VoucherTypeOwnerSystem' => '',
        'AuxiliaryNumber' => '',
        'IsCurrencyBased' => false,
        'IsExternal' => false,
        'CreatorName' => '',
        'StateTitle' => '',
    ];

    /**
     * Voucher item fields with a neutral default. `RowNumber` is handled apart
     * because its default depends on the position of the item.
     *
     * @var array<string, string|int|float|bool>
     */
    private const ITEM_DEFAULTS = [
        'VoucherItemID' => 0,
        'CurrencyAmount' => 0,
        'BaseCurrencyAmount' => 0,
        'CurrencyCredit' => 0,
        'CurrencyDebit' => 0,
        'CurrencyRef' => 0,
        'OperationalCurrencyExchangeRate' => 0,
        'OperationalCurrencyExchangeRateRef' => 0,
        'BaseCurrencyExchangeRate' => 1,
        'BaseCurrencyExchangeRateRef' => 0,
        'DL' => '',
        'DLTypeRef' => 0,
        'Description' => '',
        'Description_En' => '',
        'FollowUpDate' => '',
        'FollowUpNumber' => '',
        'SLCode' => '',
        'ExtraData' => '',
        'TaxAccountType' => 0,
        'TransactionType' => 0,
        'TaxStateType' => 0,
        'PurchaseOrSale' => 0,
        'ItemOrService' => 0,
        'PartyRef' => 0,
        'TaxAmount' => 0,
        'TollAmount' => 0,
        'SLTitle' => '',
        'IsSLTraceable' => false,
        'OperationalCurrencyPrecision' => 0,
        'DLLevelTitle' => '',
        'CurrencyPrecision' => 0,
        'CurrencyTitle' => '',
        'NumberOfSLDLLevels' => 0,
        'Quantity' => 0,
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fill every optional field with its default so the payload is complete
     * before validation runs. Only the fields carrying real accounting meaning
     * stay `required`.
     */
    protected function prepareForValidation(): void
    {
        $payload = $this->all();

        // The Rahkaran field is IsCurrencyBased; older callers send IsCurrentBased.
        if ($this->isBlank($payload['IsCurrencyBased'] ?? null) && ! $this->isBlank($payload['IsCurrentBased'] ?? null)) {
            $payload['IsCurrencyBased'] = $payload['IsCurrentBased'];
        }

        foreach (self::VOUCHER_DEFAULTS as $field => $default) {
            if ($this->isBlank($payload[$field] ?? null)) {
                $payload[$field] = $default;
            }
        }

        $items = $payload['VoucherItemData'] ?? null;

        if (is_array($items)) {
            $rowNumber = 0;
            $defaults = $this->itemDefaults();

            foreach ($items as $index => $item) {
                $rowNumber++;

                if (! is_array($item)) {
                    continue;
                }

                foreach ($defaults as $field => $default) {
                    if ($this->isBlank($item[$field] ?? null)) {
                        $item[$field] = $default;
                    }
                }

                if ($this->isBlank($item['RowNumber'] ?? null)) {
                    $item['RowNumber'] = $rowNumber;
                }

                $items[$index] = $item;
            }

            $payload['VoucherItemData'] = $items;
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'BranchRef' => ['required', 'integer'],
            'Date' => ['nullable', 'string'],
            'Description' => ['nullable', 'string'],
            'Description_En' => ['nullable', 'string'],
            'FiscalYearRef' => ['required', 'integer'],
            'LedgerRef' => ['required', 'integer'],
            'VoucherTypeRef' => ['required', 'integer'],
            'VoucherTypeOwnerSystem' => ['nullable', 'string'],
            'VoucherTypeCode' => ['required', 'integer'],
            'Number' => ['required', 'integer'],
            'AuxiliaryNumber' => ['nullable', 'string'],
            'IsCurrencyBased' => ['nullable', 'boolean'],
            'State' => ['required', Rule::enum(VoucherStateEnum::class)],
            'IsExternal' => ['nullable', 'boolean'],
            'Creator' => ['required', 'integer'],
            'CreatorName' => ['nullable', 'string'],
            'StateTitle' => ['nullable', 'string'],
            'VoucherItemData' => ['required', 'array', 'min:1'],

            // Only the accounting-relevant item fields stay required.
            'VoucherItemData.*.SLRef' => ['required', 'integer'],
            'VoucherItemData.*.Debit' => ['required', 'numeric'],
            'VoucherItemData.*.Credit' => ['required', 'numeric'],

            'VoucherItemData.*.VoucherItemID' => ['nullable', 'integer'],
            'VoucherItemData.*.CurrencyAmount' => ['nullable', 'numeric'],
            'VoucherItemData.*.BaseCurrencyAmount' => ['nullable', 'numeric'],
            'VoucherItemData.*.CurrencyCredit' => ['nullable', 'numeric'],
            'VoucherItemData.*.CurrencyDebit' => ['nullable', 'numeric'],
            'VoucherItemData.*.CurrencyRef' => ['nullable', 'integer'],
            'VoucherItemData.*.BaseCurrencyRef' => ['nullable', 'integer'],
            'VoucherItemData.*.OperationalCurrencyExchangeRate' => ['nullable', 'numeric'],
            'VoucherItemData.*.OperationalCurrencyExchangeRateRef' => ['nullable', 'integer'],
            'VoucherItemData.*.BaseCurrencyExchangeRate' => ['nullable', 'numeric'],
            'VoucherItemData.*.BaseCurrencyExchangeRateRef' => ['nullable', 'integer'],
            'VoucherItemData.*.DL' => ['nullable', 'string'],
            'VoucherItemData.*.DLTypeRef' => ['nullable', 'integer'],
            'VoucherItemData.*.Description' => ['nullable', 'string'],
            'VoucherItemData.*.Description_En' => ['nullable', 'string'],
            'VoucherItemData.*.FollowUpDate' => ['nullable', 'string'],
            'VoucherItemData.*.FollowUpNumber' => ['nullable', 'string'],
            'VoucherItemData.*.RowNumber' => ['nullable', 'integer'],
            'VoucherItemData.*.SLCode' => ['nullable', 'string'],
            'VoucherItemData.*.ExtraData' => ['nullable', 'string'],
            'VoucherItemData.*.TaxAccountType' => ['nullable', 'integer'],
            'VoucherItemData.*.TransactionType' => ['nullable', 'integer'],
            'VoucherItemData.*.TaxStateType' => ['nullable', 'integer'],
            'VoucherItemData.*.PurchaseOrSale' => ['nullable', 'integer'],
            'VoucherItemData.*.ItemOrService' => ['nullable', 'integer'],
            'VoucherItemData.*.PartyRef' => ['nullable', 'integer'],
            'VoucherItemData.*.TaxAmount' => ['nullable', 'numeric'],
            'VoucherItemData.*.TollAmount' => ['nullable', 'numeric'],
            'VoucherItemData.*.SLTitle' => ['nullable', 'string'],
            'VoucherItemData.*.IsSLTraceable' => ['nullable', 'boolean'],
            'VoucherItemData.*.OperationalCurrencyPrecision' => ['nullable', 'integer'],
            'VoucherItemData.*.DLLevelTitle' => ['nullable', 'string'],
            'VoucherItemData.*.CurrencyPrecision' => ['nullable', 'integer'],
            'VoucherItemData.*.CurrencyTitle' => ['nullable', 'string'],
            'VoucherItemData.*.BaseCurrencyPrecision' => ['nullable', 'integer'],
            'VoucherItemData.*.BaseCurrencyTitle' => ['nullable', 'string'],
            'VoucherItemData.*.NumberOfSLDLLevels' => ['nullable', 'integer'],
            'VoucherItemData.*.Quantity' => ['nullable', 'numeric'],
        ];
    }

    public function toDto(): RegisterVoucherData
    {
        $data = $this->validated();

        return new RegisterVoucherData(
            BranchRef: (int) $data['BranchRef'],
            Date: $this->asString($data['Date'] ?? null),
            Description: $this->asString($data['Description'] ?? null),
            Description_En: $this->asString($data['Description_En'] ?? null),
            FiscalYearRef: (int) $data['FiscalYearRef'],
            LedgerRef: (int) $data['LedgerRef'],
            VoucherTypeRef: (int) $data['VoucherTypeRef'],
            VoucherTypeOwnerSystem: $this->asString($data['VoucherTypeOwnerSystem'] ?? null),
            VoucherTypeCode: (int) $data['VoucherTypeCode'],
            Number: (int) $data['Number'],
            AuxiliaryNumber: $this->asString($data['AuxiliaryNumber'] ?? null),
            IsCurrencyBased: $this->asBool($data['IsCurrencyBased'] ?? null),
            State: VoucherStateEnum::from((int) $data['State']),
            IsExternal: $this->asBool($data['IsExternal'] ?? null),
            Creator: (int) $data['Creator'],
            CreatorName: $this->asString($data['CreatorName'] ?? null),
            StateTitle: $this->asString($data['StateTitle'] ?? null),
            VoucherItemData: array_values(array_map(
                fn (array $item): VoucherItemData => $this->toItemDto($item),
                $data['VoucherItemData'],
            )),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function toItemDto(array $item): VoucherItemData
    {
        return new VoucherItemData(
            VoucherItemID: $this->asInt($item['VoucherItemID'] ?? null),
            SLRef: $this->asInt($item['SLRef'] ?? null),
            Debit: $this->asFloat($item['Debit'] ?? null),
            Credit: $this->asFloat($item['Credit'] ?? null),
            CurrencyAmount: $this->asFloat($item['CurrencyAmount'] ?? null),
            BaseCurrencyAmount: $this->asFloat($item['BaseCurrencyAmount'] ?? null),
            CurrencyCredit: $this->asFloat($item['CurrencyCredit'] ?? null),
            CurrencyDebit: $this->asFloat($item['CurrencyDebit'] ?? null),
            CurrencyRef: $this->asInt($item['CurrencyRef'] ?? null),
            BaseCurrencyRef: $this->asInt($item['BaseCurrencyRef'] ?? null),
            OperationalCurrencyExchangeRate: $this->asFloat($item['OperationalCurrencyExchangeRate'] ?? null),
            OperationalCurrencyExchangeRateRef: $this->asInt($item['OperationalCurrencyExchangeRateRef'] ?? null),
            BaseCurrencyExchangeRate: $this->asFloat($item['BaseCurrencyExchangeRate'] ?? null),
            BaseCurrencyExchangeRateRef: $this->asInt($item['BaseCurrencyExchangeRateRef'] ?? null),
            DL: $this->asString($item['DL'] ?? null),
            DLTypeRef: $this->asInt($item['DLTypeRef'] ?? null),
            Description: $this->asString($item['Description'] ?? null),
            Description_En: $this->asString($item['Description_En'] ?? null),
            FollowUpDate: $this->asString($item['FollowUpDate'] ?? null),
            FollowUpNumber: $this->asString($item['FollowUpNumber'] ?? null),
            RowNumber: $this->asInt($item['RowNumber'] ?? null),
            SLCode: $this->asString($item['SLCode'] ?? null),
            ExtraData: $this->asString($item['ExtraData'] ?? null),
            TaxAccountType: $this->asInt($item['TaxAccountType'] ?? null),
            TransactionType: $this->asInt($item['TransactionType'] ?? null),
            TaxStateType: $this->asInt($item['TaxStateType'] ?? null),
            PurchaseOrSale: $this->asInt($item['PurchaseOrSale'] ?? null),
            ItemOrService: $this->asInt($item['ItemOrService'] ?? null),
            PartyRef: $this->asInt($item['PartyRef'] ?? null),
            TaxAmount: $this->asFloat($item['TaxAmount'] ?? null),
            TollAmount: $this->asFloat($item['TollAmount'] ?? null),
            SLTitle: $this->asString($item['SLTitle'] ?? null),
            IsSLTraceable: $this->asBool($item['IsSLTraceable'] ?? null),
            OperationalCurrencyPrecision: $this->asInt($item['OperationalCurrencyPrecision'] ?? null),
            DLLevelTitle: $this->asString($item['DLLevelTitle'] ?? null),
            CurrencyPrecision: $this->asInt($item['CurrencyPrecision'] ?? null),
            CurrencyTitle: $this->asString($item['CurrencyTitle'] ?? null),
            BaseCurrencyPrecision: $this->asInt($item['BaseCurrencyPrecision'] ?? null),
            BaseCurrencyTitle: $this->asString($item['BaseCurrencyTitle'] ?? null),
            NumberOfSLDLLevels: $this->asInt($item['NumberOfSLDLLevels'] ?? null),
            Quantity: $this->asFloat($item['Quantity'] ?? null),
        );
    }

    /**
     * Item defaults, including the ones that come from configuration.
     *
     * @return array<string, string|int|float|bool>
     */
    private function itemDefaults(): array
    {
        return self::ITEM_DEFAULTS + [
            'BaseCurrencyRef' => (int) config('services.voucher.defaults.base_currency_ref', 0),
            'BaseCurrencyPrecision' => (int) config('services.voucher.defaults.base_currency_precision', 0),
            'BaseCurrencyTitle' => (string) config('services.voucher.defaults.base_currency_title', ''),
        ];
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function asString(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private function asInt(mixed $value): int
    {
        return (int) ($value ?? 0);
    }

    private function asFloat(mixed $value): float
    {
        return (float) ($value ?? 0);
    }

    private function asBool(mixed $value): bool
    {
        return filter_var($value ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
