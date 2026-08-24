<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;
use InvalidArgumentException;

readonly class VoucherItemData implements PayloadData
{
    /** @param  array<string, mixed>|null  $ExtraInfo */
    public function __construct(
        public float $Debit,
        public float $Credit,
        public ?int $SLRef = null,
        public ?int $ID = null,
        public ?float $CurrencyAmount = null,
        public ?float $BaseCurrencyAmount = null,
        public ?float $CurrencyCredit = null,
        public ?float $CurrencyDebit = null,
        public ?int $CurrencyRef = null,
        public ?int $BaseCurrencyRef = null,
        public ?float $OperationalCurrencyExchangeRate = null,
        public ?int $OperationalCurrencyExchangeRateRef = null,
        public ?float $BaseCurrencyExchangeRate = null,
        public ?int $BaseCurrencyExchangeRateRef = null,
        public ?string $Description = null,
        public ?string $Description_En = null,
        public ?string $FollowUpDate = null,
        public ?string $FollowUpNumber = null,
        public ?int $RowNumber = null,
        public ?string $SLCode = null,
        public ?string $ExtraData = null,
        public ?int $TaxAccountType = null,
        public ?int $TransactionType = null,
        public ?int $TaxStateType = null,
        public ?int $PurchaseOrSale = null,
        public ?int $ItemOrService = null,
        public ?int $PartyRef = null,
        public ?float $TaxAmount = null,
        public ?float $TollAmount = null,
        public ?string $SLTitle = null,
        public ?bool $IsSLTraceable = null,
        public ?int $OperationalCurrencyPrecision = null,
        public ?int $CurrencyPrecision = null,
        public ?string $CurrencyTitle = null,
        public ?int $BaseCurrencyPrecision = null,
        public ?string $BaseCurrencyTitle = null,
        public ?int $NumberOfSLDLLevels = null,
        public ?float $Quantity = null,
        public ?array $ExtraInfo = null,
        public ?string $DL4 = null,
        public ?string $DL5 = null,
        public ?string $DL6 = null,
        public ?string $DL7 = null,
        public ?string $DL8 = null,
        public ?string $DL9 = null,
        public ?string $DL10 = null,
        public ?string $DL11 = null,
        public ?string $DL12 = null,
        public ?string $DL13 = null,
        public ?string $DL14 = null,
        public ?string $DL15 = null,
        public ?string $DL16 = null,
        public ?string $DL17 = null,
        public ?string $DL18 = null,
        public ?string $DL19 = null,
        public ?string $DL20 = null,
        public ?string $DLLevel4Title = null,
        public ?string $DLLevel5Title = null,
        public ?string $DLLevel6Title = null,
        public ?string $DLLevel7Title = null,
        public ?string $DLLevel8Title = null,
        public ?string $DLLevel9Title = null,
        public ?string $DLLevel10Title = null,
        public ?string $DLLevel11Title = null,
        public ?string $DLLevel12Title = null,
        public ?string $DLLevel13Title = null,
        public ?string $DLLevel14Title = null,
        public ?string $DLLevel15Title = null,
        public ?string $DLLevel16Title = null,
        public ?string $DLLevel17Title = null,
        public ?string $DLLevel18Title = null,
        public ?string $DLLevel19Title = null,
        public ?string $DLLevel20Title = null,
        public ?int $DLTypeRef4 = null,
        public ?int $DLTypeRef5 = null,
        public ?int $DLTypeRef6 = null,
        public ?int $DLTypeRef7 = null,
        public ?int $DLTypeRef8 = null,
        public ?int $DLTypeRef9 = null,
        public ?int $DLTypeRef10 = null,
        public ?int $DLTypeRef11 = null,
        public ?int $DLTypeRef12 = null,
        public ?int $DLTypeRef13 = null,
        public ?int $DLTypeRef14 = null,
        public ?int $DLTypeRef15 = null,
        public ?int $DLTypeRef16 = null,
        public ?int $DLTypeRef17 = null,
        public ?int $DLTypeRef18 = null,
        public ?int $DLTypeRef19 = null,
        public ?int $DLTypeRef20 = null,
    ) {
        if ($this->Debit == 0.0 && $this->Credit == 0.0) {
            throw new InvalidArgumentException('At least one of Debit or Credit must be greater than zero.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return self::withoutNulls(get_object_vars($this));
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
