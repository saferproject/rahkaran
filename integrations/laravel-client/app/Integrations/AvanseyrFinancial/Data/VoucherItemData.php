<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;

readonly class VoucherItemData implements PayloadData
{
    public function __construct(
        public int $SLRef,
        public float $Debit,
        public float $Credit,
        public int $VoucherItemID = 0,
        public float $CurrencyAmount = 0,
        public float $BaseCurrencyAmount = 0,
        public float $CurrencyCredit = 0,
        public float $CurrencyDebit = 0,
        public int $CurrencyRef = 0,
        public int $BaseCurrencyRef = 0,
        public float $OperationalCurrencyExchangeRate = 0,
        public int $OperationalCurrencyExchangeRateRef = 0,
        public float $BaseCurrencyExchangeRate = 0,
        public int $BaseCurrencyExchangeRateRef = 0,
        public string $DL = '',
        public int $DLTypeRef = 0,
        public string $Description = '',
        public string $Description_En = '',
        public string $FollowUpDate = '',
        public string $FollowUpNumber = '',
        public int $RowNumber = 0,
        public string $SLCode = '',
        public string $ExtraData = '',
        public int $TaxAccountType = 0,
        public int $TransactionType = 0,
        public int $TaxStateType = 0,
        public int $PurchaseOrSale = 0,
        public int $ItemOrService = 0,
        public int $PartyRef = 0,
        public float $TaxAmount = 0,
        public float $TollAmount = 0,
        public string $SLTitle = '',
        public bool $IsSLTraceable = false,
        public int $OperationalCurrencyPrecision = 0,
        public string $DLLevelTitle = '',
        public int $CurrencyPrecision = 0,
        public string $CurrencyTitle = '',
        public int $BaseCurrencyPrecision = 0,
        public string $BaseCurrencyTitle = '',
        public int $NumberOfSLDLLevels = 0,
        public int $Quantity = 1,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
