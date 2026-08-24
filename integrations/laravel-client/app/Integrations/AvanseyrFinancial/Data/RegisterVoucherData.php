<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;
use App\Integrations\AvanseyrFinancial\Enums\VoucherState;
use InvalidArgumentException;

readonly class RegisterVoucherData implements PayloadData
{
    /**
     * @param  list<VoucherItemData>  $VoucherItemData
     * @param  array<string, mixed>|null  $ExtraInfo
     * @param  list<array<string, mixed>>|null  $VoucherReferenceInfo
     */
    public function __construct(
        public int $BranchRef,
        public int|string $Date,
        public int $FiscalYearRef,
        public int $LedgerRef,
        public int $VoucherTypeRef,
        public int $VoucherTypeCode,
        public int $Number,
        public int $Creator,
        public array $VoucherItemData,
        public ?string $VoucherTypeOwnerSystem = null,
        public ?string $Description = null,
        public ?string $Description_En = null,
        public ?string $AuxiliaryNumber = null,
        public ?bool $IsCurrencyBased = null,
        public VoucherState $State = VoucherState::WithNoRevision,
        public ?bool $IsExternal = null,
        public ?string $CreatorName = null,
        public ?string $StateTitle = null,
        public ?array $ExtraInfo = null,
        public ?array $VoucherReferenceInfo = null,
    ) {
        if ($this->VoucherItemData === []) {
            throw new InvalidArgumentException('VoucherItemData must contain at least one voucher item.');
        }

        foreach ($this->VoucherItemData as $item) {
            if (! $item instanceof VoucherItemData) {
                throw new InvalidArgumentException('VoucherItemData must contain only VoucherItemData objects.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return self::withoutNulls([
            'BranchRef' => $this->BranchRef,
            'Date' => $this->Date,
            'Description' => $this->Description,
            'Description_En' => $this->Description_En,
            'ExtraInfo' => $this->ExtraInfo,
            'FiscalYearRef' => $this->FiscalYearRef,
            'LedgerRef' => $this->LedgerRef,
            'VoucherTypeRef' => $this->VoucherTypeRef,
            'VoucherTypeOwnerSystem' => $this->VoucherTypeOwnerSystem,
            'VoucherTypeCode' => $this->VoucherTypeCode,
            'Number' => $this->Number,
            'AuxiliaryNumber' => $this->AuxiliaryNumber,
            'IsCurrencyBased' => $this->IsCurrencyBased,
            'State' => $this->State->value,
            'IsExternal' => $this->IsExternal,
            'Creator' => $this->Creator,
            'CreatorName' => $this->CreatorName,
            'StateTitle' => $this->StateTitle,
            'VoucherItemData' => array_map(
                fn (VoucherItemData $item): array => $item->toArray(),
                $this->VoucherItemData,
            ),
            'VoucherReferenceInfo' => $this->VoucherReferenceInfo,
        ]);
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
