<?php

namespace App\DTO;

use InvalidArgumentException;

class VoucherItemData
{
    /**
     * @param  array<int, array{DL?: string|null, DLLevelTitle?: string|null, DLTypeRef?: int|null}>  $detailLevels
     * @param  array<string, mixed>|null  $ExtraInfo
     */
    public function __construct(
        public ?float $Debit = null,
        public ?float $Credit = null,
        public ?int $RowNumber = null,
        public ?int $ID = null,
        public ?float $BaseCurrencyAmount = null,
        public ?float $BaseCurrencyExchangeRate = null,
        public ?int $BaseCurrencyExchangeRateRef = null,
        public ?int $BaseCurrencyPrecision = null,
        public ?int $BaseCurrencyRef = null,
        public ?string $BaseCurrencyTitle = null,
        public ?float $CurrencyAmount = null,
        public ?float $CurrencyCredit = null,
        public ?float $CurrencyDebit = null,
        public ?int $CurrencyPrecision = null,
        public ?int $CurrencyRef = null,
        public ?string $CurrencyTitle = null,
        public array $detailLevels = [],
        public ?string $Description = null,
        public ?string $Description_En = null,
        public ?string $ExtraData = null,
        public ?array $ExtraInfo = null,
        public ?string $FollowUpDate = null,
        public ?string $FollowUpNumber = null,
        public ?bool $IsSLTraceable = null,
        public ?int $ItemOrService = null,
        public ?int $NumberOfSLDLLevels = null,
        public ?float $OperationalCurrencyExchangeRate = null,
        public ?int $OperationalCurrencyExchangeRateRef = null,
        public ?int $OperationalCurrencyPrecision = null,
        public ?int $PartyRef = null,
        public ?int $PurchaseOrSale = null,
        public ?float $Quantity = null,
        public ?string $SLCode = null,
        public ?int $SLRef = null,
        public ?string $SLTitle = null,
        public ?int $TaxAccountType = null,
        public ?float $TaxAmount = null,
        public ?int $TaxStateType = null,
        public ?float $TollAmount = null,
        public ?int $TransactionType = null,
    ) {
        if (($this->Debit ?? 0) < 0 || ($this->Credit ?? 0) < 0) {
            throw new InvalidArgumentException('Debit and Credit cannot be negative.');
        }

        $hasDebit = $this->Debit !== null && $this->Debit > 0;
        $hasCredit = $this->Credit !== null && $this->Credit > 0;

        if ($hasDebit === $hasCredit) {
            throw new InvalidArgumentException('Exactly one of Debit or Credit must be greater than zero.');
        }

        $this->Debit = $hasDebit ? $this->Debit : null;
        $this->Credit = $hasCredit ? $this->Credit : null;

        $this->dropCurrencyBlocksWithoutCurrency();
    }

    /**
     * Rahkaran resolves the currency of every article it builds and answers
     * "در آرتیکل N ارز نامعتبر است" when the ref does not point at a currency.
     * A ref of 0 is not a currency, so the block it belongs to is dropped
     * instead of being sent as a set of zeros.
     */
    private function dropCurrencyBlocksWithoutCurrency(): void
    {
        if (($this->CurrencyRef ?? 0) <= 0) {
            $this->CurrencyRef = null;
            $this->CurrencyAmount = null;
            $this->CurrencyCredit = null;
            $this->CurrencyDebit = null;
            $this->CurrencyPrecision = null;
            $this->CurrencyTitle = null;
        }

        if (($this->BaseCurrencyRef ?? 0) <= 0) {
            $this->BaseCurrencyRef = null;
            $this->BaseCurrencyAmount = null;
            $this->BaseCurrencyExchangeRate = null;
            $this->BaseCurrencyExchangeRateRef = null;
            $this->BaseCurrencyPrecision = null;
            $this->BaseCurrencyTitle = null;
        }

        if (($this->OperationalCurrencyExchangeRateRef ?? 0) <= 0 && ($this->OperationalCurrencyExchangeRate ?? 0) <= 0) {
            $this->OperationalCurrencyExchangeRate = null;
            $this->OperationalCurrencyExchangeRateRef = null;
            $this->OperationalCurrencyPrecision = null;
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'BaseCurrencyAmount' => $this->BaseCurrencyAmount,
            'BaseCurrencyExchangeRate' => $this->BaseCurrencyExchangeRate,
            'BaseCurrencyExchangeRateRef' => $this->BaseCurrencyExchangeRateRef,
            'BaseCurrencyPrecision' => $this->BaseCurrencyPrecision,
            'BaseCurrencyRef' => $this->BaseCurrencyRef,
            'BaseCurrencyTitle' => $this->BaseCurrencyTitle,
            'Credit' => $this->Credit,
            'CurrencyAmount' => $this->CurrencyAmount,
            'CurrencyCredit' => $this->CurrencyCredit,
            'CurrencyDebit' => $this->CurrencyDebit,
            'CurrencyPrecision' => $this->CurrencyPrecision,
            'CurrencyRef' => $this->CurrencyRef,
            'CurrencyTitle' => $this->CurrencyTitle,
        ];

        foreach (range(4, 20) as $level) {
            $detail = $this->detailLevels[$level] ?? [];
            $data["DL{$level}"] = $detail['DL'] ?? null;
            $data["DLLevel{$level}Title"] = $detail['DLLevelTitle'] ?? null;
            $data["DLTypeRef{$level}"] = $detail['DLTypeRef'] ?? null;
        }

        $data += [
            'Debit' => $this->Debit,
            'Description' => $this->Description,
            'Description_En' => $this->Description_En,
            'ExtraData' => $this->ExtraData,
            'ExtraInfo' => $this->ExtraInfo,
            'FollowUpDate' => $this->FollowUpDate,
            'FollowUpNumber' => $this->FollowUpNumber,
            'ID' => $this->ID,
            'IsSLTraceable' => $this->IsSLTraceable,
            'ItemOrService' => $this->ItemOrService,
            'NumberOfSLDLLevels' => $this->NumberOfSLDLLevels,
            'OperationalCurrencyExchangeRate' => $this->OperationalCurrencyExchangeRate,
            'OperationalCurrencyExchangeRateRef' => $this->OperationalCurrencyExchangeRateRef,
            'OperationalCurrencyPrecision' => $this->OperationalCurrencyPrecision,
            'PartyRef' => $this->PartyRef,
            'PurchaseOrSale' => $this->PurchaseOrSale,
            'Quantity' => $this->Quantity,
            'RowNumber' => $this->RowNumber,
            'SLCode' => $this->SLCode,
            'SLRef' => $this->SLRef,
            'SLTitle' => $this->SLTitle,
            'TaxAccountType' => $this->TaxAccountType,
            'TaxAmount' => $this->TaxAmount,
            'TaxStateType' => $this->TaxStateType,
            'TollAmount' => $this->TollAmount,
            'TransactionType' => $this->TransactionType,
        ];

        return self::withoutNulls($data);
    }

    /** @return array<mixed> */
    private static function withoutNulls(array $data): array
    {
        $isList = array_is_list($data);

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = self::withoutNulls($value);
            }
        }

        return $isList ? array_values($data) : $data;
    }
}
