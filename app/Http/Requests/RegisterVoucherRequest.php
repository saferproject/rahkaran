<?php

namespace App\Http\Requests;

use App\DTO\RegisterVoucherData;
use App\DTO\VoucherItemData;
use App\Enums\VoucherStateEnum;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

class RegisterVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->all();

        if (! array_key_exists('IsCurrencyBased', $payload) && array_key_exists('IsCurrentBased', $payload)) {
            $payload['IsCurrencyBased'] = $payload['IsCurrentBased'];
        }

        if (! array_key_exists('VoucherItemData', $payload) && isset($payload['VoucherItems'])) {
            $payload['VoucherItemData'] = $payload['VoucherItems'];
        }

        $items = $payload['VoucherItemData'] ?? null;

        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (! array_key_exists('ID', $item) && array_key_exists('VoucherItemID', $item)) {
                    $item['ID'] = $item['VoucherItemID'];
                }

                foreach (
                    [
                        'DL' => 'DL4',
                        'DLLevelTitle' => 'DLLevel4Title',
                        'DLTypeRef' => 'DLTypeRef4',
                    ] as $legacyField => $documentedField
                ) {
                    if (! array_key_exists($documentedField, $item) && array_key_exists($legacyField, $item)) {
                        $item[$documentedField] = $item[$legacyField];
                    }
                }

                foreach (['FollowUpNumber', 'FollowUpDate'] as $followUpField) {
                    if ($this->isOmittable($item[$followUpField] ?? null)) {
                        $item[$followUpField] = null;
                    }
                }

                $items[$index] = $item;
            }

            $payload['VoucherItemData'] = $items;
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $rules = [
            'BranchRef' => ['required', 'integer'],
            'Date' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    $this->asWcfDate($value);
                } catch (Throwable) {
                    $fail("The {$attribute} field must be a valid date or Unix timestamp.");
                }
            }],
            'Description' => ['nullable', 'string'],
            'Description_En' => ['nullable', 'string'],
            'ExtraInfo' => ['nullable', 'array'],
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
            'VoucherReferenceInfo' => ['nullable', 'array'],
            'VoucherItemData' => ['required', 'array', 'min:1'],

            'VoucherItemData.*.SLRef' => ['nullable', 'integer'],
            'VoucherItemData.*.Debit' => ['nullable', 'numeric', 'min:0'],
            'VoucherItemData.*.Credit' => ['nullable', 'numeric', 'min:0'],

            'VoucherItemData.*.ID' => ['nullable', 'integer'],
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
            'VoucherItemData.*.Description' => ['nullable', 'string'],
            'VoucherItemData.*.Description_En' => ['nullable', 'string'],
            'VoucherItemData.*.ExtraInfo' => ['nullable', 'array'],
            'VoucherItemData.*.FollowUpDate' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    $this->asWcfDate($value);
                } catch (Throwable) {
                    $fail("The {$attribute} field must be a valid date or Unix timestamp.");
                }
            }],
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
            'VoucherItemData.*.CurrencyPrecision' => ['nullable', 'integer'],
            'VoucherItemData.*.CurrencyTitle' => ['nullable', 'string'],
            'VoucherItemData.*.BaseCurrencyPrecision' => ['nullable', 'integer'],
            'VoucherItemData.*.BaseCurrencyTitle' => ['nullable', 'string'],
            'VoucherItemData.*.NumberOfSLDLLevels' => ['nullable', 'integer'],
            'VoucherItemData.*.Quantity' => ['nullable', 'numeric'],
        ];

        foreach (range(4, 20) as $level) {
            $rules["VoucherItemData.*.DL{$level}"] = ['nullable', 'string'];
            $rules["VoucherItemData.*.DLLevel{$level}Title"] = ['nullable', 'string'];
            $rules["VoucherItemData.*.DLTypeRef{$level}"] = ['nullable', 'integer'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('VoucherItemData', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $debit = is_numeric($item['Debit'] ?? null) ? (float) $item['Debit'] : 0.0;
                $credit = is_numeric($item['Credit'] ?? null) ? (float) $item['Credit'] : 0.0;
                $hasDebit = $debit > 0;
                $hasCredit = $credit > 0;

                if ($hasDebit === $hasCredit) {
                    $validator->errors()->add(
                        "VoucherItemData.{$index}.Debit",
                        'Exactly one of Debit or Credit must be greater than zero.',
                    );
                }
            }
        });
    }

    public function toDto(): RegisterVoucherData
    {
        $data = $this->validated();

        return new RegisterVoucherData(
            BranchRef: (int) $data['BranchRef'],
            FiscalYearRef: (int) $data['FiscalYearRef'],
            LedgerRef: (int) $data['LedgerRef'],
            VoucherTypeRef: (int) $data['VoucherTypeRef'],
            VoucherTypeCode: (int) $data['VoucherTypeCode'],
            Number: (int) $data['Number'],
            State: VoucherStateEnum::from((int) $data['State']),
            Creator: (int) $data['Creator'],
            VoucherItemData: array_values(array_map(
                fn(array $item): VoucherItemData => $this->toItemDto($item),
                $data['VoucherItemData'],
            )),
            Date: $this->asWcfDate($data['Date'] ?? null),
            Description: $this->asNullableString($data['Description'] ?? null),
            Description_En: $this->asNullableString($data['Description_En'] ?? null),
            VoucherTypeOwnerSystem: $this->asNullableString($data['VoucherTypeOwnerSystem'] ?? null),
            AuxiliaryNumber: $this->asNullableString($data['AuxiliaryNumber'] ?? null),
            IsCurrencyBased: $this->asNullableBool($data['IsCurrencyBased'] ?? null),
            IsExternal: $this->asNullableBool($data['IsExternal'] ?? null),
            CreatorName: $this->asNullableString($data['CreatorName'] ?? null),
            StateTitle: $this->asNullableString(/*$data['StateTitle'] ??*/null),
            ExtraInfo: $data['ExtraInfo'] ?? null,
            VoucherReferenceInfo: $data['VoucherReferenceInfo'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function toItemDto(array $item): VoucherItemData
    {
        return new VoucherItemData(
            Debit: $this->asAccountingAmount($item['Debit'] ?? null),
            Credit: $this->asAccountingAmount($item['Credit'] ?? null),
            RowNumber: $this->asNullableInt($item['RowNumber'] ?? null),
            ID: $this->asNullableInt($item['ID'] ?? null),
            BaseCurrencyAmount: $this->asNullableFloat($item['BaseCurrencyAmount'] ?? null),
            BaseCurrencyExchangeRate: $this->asNullableFloat($item['BaseCurrencyExchangeRate'] ?? null),
            BaseCurrencyExchangeRateRef: $this->asNullableInt($item['BaseCurrencyExchangeRateRef'] ?? null),
            BaseCurrencyPrecision: $this->asNullableInt($item['BaseCurrencyPrecision'] ?? null),
            BaseCurrencyRef: $this->asNullableInt($item['BaseCurrencyRef'] ?? null),
            BaseCurrencyTitle: $this->asNullableString($item['BaseCurrencyTitle'] ?? null),
            CurrencyAmount: $this->asNullableFloat($item['CurrencyAmount'] ?? null),
            CurrencyCredit: $this->asNullableFloat($item['CurrencyCredit'] ?? null),
            CurrencyDebit: $this->asNullableFloat($item['CurrencyDebit'] ?? null),
            CurrencyPrecision: $this->asNullableInt($item['CurrencyPrecision'] ?? null),
            CurrencyRef: $this->asNullableInt($item['CurrencyRef'] ?? null),
            CurrencyTitle: $this->asNullableString($item['CurrencyTitle'] ?? null),
            detailLevels: $this->detailLevels($item),
            Description: $this->asNullableString($item['Description'] ?? null),
            Description_En: $this->asNullableString($item['Description_En'] ?? null),
            ExtraData: $this->asNullableString($item['ExtraData'] ?? null),
            ExtraInfo: $item['ExtraInfo'] ?? null,
            FollowUpDate: $this->asWcfDate($item['FollowUpDate'] ?? null),
            FollowUpNumber: $this->asNullableString($item['FollowUpNumber'] ?? null),
            IsSLTraceable: $this->asNullableBool($item['IsSLTraceable'] ?? null),
            ItemOrService: $this->asNullableInt($item['ItemOrService'] ?? null),
            NumberOfSLDLLevels: $this->asNullableInt(/*$item['NumberOfSLDLLevels'] ??*/null),
            OperationalCurrencyExchangeRate: $this->asNullableFloat($item['OperationalCurrencyExchangeRate'] ?? null),
            OperationalCurrencyExchangeRateRef: $this->asNullableInt($item['OperationalCurrencyExchangeRateRef'] ?? null),
            OperationalCurrencyPrecision: $this->asNullableInt($item['OperationalCurrencyPrecision'] ?? null),
            PartyRef: $this->asNullableInt($item['PartyRef'] ?? null),
            PurchaseOrSale: $this->asNullableInt($item['PurchaseOrSale'] ?? null),
            Quantity: $this->asNullableFloat(/*$item['Quantity'] ??*/null),
            SLCode: $this->asNullableString($item['SLCode'] ?? null),
            SLRef: $this->asNullableInt(/* $item['SLRef'] ?? */null),
            SLTitle: $this->asNullableString($item['SLTitle'] ?? null),
            TaxAccountType: $this->asNullableInt($item['TaxAccountType'] ?? null),
            TaxAmount: $this->asNullableFloat($item['TaxAmount'] ?? null),
            TaxStateType: $this->asNullableInt($item['TaxStateType'] ?? null),
            TollAmount: $this->asNullableFloat($item['TollAmount'] ?? null),
            TransactionType: $this->asNullableInt($item['TransactionType'] ?? null),
        );
    }

    /** @return array<int, array{DL?: string|null, DLLevelTitle?: string|null, DLTypeRef?: int|null}> */
    private function detailLevels(array $item): array
    {
        $details = [];

        foreach (range(4, 20) as $level) {
            $detail = array_filter([
                'DL' => $this->asNullableString($item["DL{$level}"] ?? null),
                'DLLevelTitle' => $this->asNullableString($item["DLLevel{$level}Title"] ?? null),
                'DLTypeRef' => $this->asNullableInt($item["DLTypeRef{$level}"] ?? null),
            ], static fn(mixed $value): bool => $value !== null);

            if ($detail !== []) {
                $details[$level] = $detail;
            }
        }

        return $details;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function isOmittable(mixed $value): bool
    {
        if ($this->isBlank($value)) {
            return true;
        }

        return ! is_bool($value) && is_numeric($value) && (float) $value === 0.0;
    }

    private function asNullableString(mixed $value): ?string
    {
        return $this->isOmittable($value) ? null : (string) $value;
    }

    private function asInt(mixed $value): int
    {
        return (int) ($value ?? 0);
    }

    private function asNullableInt(mixed $value): ?int
    {
        return $this->isOmittable($value) ? null : (int) $value;
    }

    private function asNullableFloat(mixed $value): ?float
    {
        return $this->isOmittable($value) ? null : (float) $value;
    }

    private function asAccountingAmount(mixed $value): ?float
    {
        $amount = $this->asNullableFloat($value);

        return $amount !== null && $amount > 0 ? $amount : null;
    }

    private function asFloat(mixed $value): float
    {
        return (float) ($value ?? 0);
    }

    private function asNullableBool(mixed $value): ?bool
    {
        return $this->isBlank($value) ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function asWcfDate(mixed $value): ?string
    {
        if ($this->isOmittable($value)) {
            return null;
        }

        $value = (string) $value;

        if (preg_match('/^\\/Date\\(-?\\d+(?:[+-]\\d{4})?\\)\\/$/', $value) === 1) {
            return $value;
        }

        $timezone = new DateTimeZone((string) config('services.voucher.timezone', 'Asia/Tehran'));

        if (is_numeric($value)) {
            $numericTimestamp = (int) $value;
            $isMilliseconds = abs($numericTimestamp) >= 100_000_000_000;
            $milliseconds = $isMilliseconds ? $numericTimestamp : $numericTimestamp * 1000;
            $date = (new DateTimeImmutable('@' . intdiv($milliseconds, 1000)))->setTimezone($timezone);
        } else {
            $date = (new DateTimeImmutable($value, $timezone))->setTimezone($timezone);
            $milliseconds = ($date->getTimestamp() * 1000) + (int) $date->format('v');
        }

        return sprintf('/Date(%d%s)/', $milliseconds, $date->format('O'));
    }
}
