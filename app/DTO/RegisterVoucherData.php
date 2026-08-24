<?php

namespace App\DTO;

use App\Enums\VoucherStateEnum;

class RegisterVoucherData
{
    /**
     * @param  list<VoucherItemData>  $VoucherItemData
     * @param  array<string, mixed>|null  $ExtraInfo
     * @param  list<array<string, mixed>>|null  $VoucherReferenceInfo
     */
    public function __construct(
        public int $BranchRef,
        public int $FiscalYearRef,
        public int $LedgerRef,
        public int $VoucherTypeRef,
        public int $VoucherTypeCode,
        public int $Number,
        public VoucherStateEnum $State,
        public int $Creator,
        public array $VoucherItemData,
        public ?string $Date = null,
        public ?string $Description = null,
        public ?string $Description_En = null,
        public ?string $VoucherTypeOwnerSystem = null,
        public ?string $AuxiliaryNumber = null,
        public ?bool $IsCurrencyBased = null,
        public ?bool $IsExternal = null,
        public ?string $CreatorName = null,
        public ?string $StateTitle = null,
        public ?array $ExtraInfo = null,
        public ?array $VoucherReferenceInfo = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return self::withoutNulls([
            'AuxiliaryNumber' => $this->AuxiliaryNumber,
            'BranchRef' => $this->BranchRef,
            'Creator' => $this->Creator,
            'CreatorName' => $this->CreatorName,
            'Date' => $this->Date,
            'Description' => $this->Description,
            'Description_En' => $this->Description_En,
            'ExtraInfo' => $this->ExtraInfo,
            'FiscalYearRef' => $this->FiscalYearRef,
            'IsCurrencyBased' => $this->IsCurrencyBased,
            'IsExternal' => $this->IsExternal,
            'LedgerRef' => $this->LedgerRef,
            'Number' => $this->Number,
            'State' => $this->State->value,
            'StateTitle' => $this->StateTitle,
            'VoucherItems' => array_map(
                fn (VoucherItemData $item): array => $item->toArray(),
                $this->VoucherItemData,
            ),
            'VoucherReferenceInfo' => $this->VoucherReferenceInfo,
            'VoucherTypeCode' => $this->VoucherTypeCode,
            'VoucherTypeOwnerSystem' => $this->VoucherTypeOwnerSystem,
            'VoucherTypeRef' => $this->VoucherTypeRef,
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
