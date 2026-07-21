<?php

namespace App\Integrations\AvanseyrFinancial\Data;

use App\Integrations\AvanseyrFinancial\Contracts\PayloadData;
use App\Integrations\AvanseyrFinancial\Enums\VoucherState;
use InvalidArgumentException;

readonly class RegisterVoucherData implements PayloadData
{
    /**
     * @param  list<VoucherItemData>  $VoucherItemData
     */
    public function __construct(
        public int $BranchRef,
        public string $Date,
        public int $FiscalYearRef,
        public int $LedgerRef,
        public int $VoucherTypeRef,
        public string $VoucherTypeOwnerSystem,
        public int $VoucherTypeCode,
        public array $VoucherItemData,
        public string $Description = '',
        public string $Description_En = '',
        public int $Number = 0,
        public string $AuxiliaryNumber = '',
        public bool $IsCurrentBased = false,
        public VoucherState $State = VoucherState::WithNoRevision,
        public bool $IsExternal = true,
        public int $Creator = 0,
        public string $CreatorName = '',
        public string $StateTitle = '',
    ) {
        foreach ($this->VoucherItemData as $item) {
            if (! $item instanceof VoucherItemData) {
                throw new InvalidArgumentException('VoucherItemData must contain only VoucherItemData objects.');
            }
        }
    }

    public function toArray(): array
    {
        return [
            'BranchRef' => $this->BranchRef,
            'Date' => $this->Date,
            'Description' => $this->Description,
            'Description_En' => $this->Description_En,
            'FiscalYearRef' => $this->FiscalYearRef,
            'LedgerRef' => $this->LedgerRef,
            'VoucherTypeRef' => $this->VoucherTypeRef,
            'VoucherTypeOwnerSystem' => $this->VoucherTypeOwnerSystem,
            'VoucherTypeCode' => $this->VoucherTypeCode,
            'Number' => $this->Number,
            'AuxiliaryNumber' => $this->AuxiliaryNumber,
            'IsCurrentBased' => $this->IsCurrentBased,
            'State' => $this->State->value,
            'IsExternal' => $this->IsExternal,
            'Creator' => $this->Creator,
            'CreatorName' => $this->CreatorName,
            'StateTitle' => $this->StateTitle,
            'VoucherItemData' => array_map(
                fn (VoucherItemData $item): array => $item->toArray(),
                $this->VoucherItemData,
            ),
        ];
    }
}
