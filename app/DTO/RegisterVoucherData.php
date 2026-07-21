<?php

namespace App\DTO;

use App\Enums\VoucherStateEnum;

class RegisterVoucherData
{
    /**
     * @param VoucherItemData[] $VoucherItemData
     */
    public function __construct(
        public int $BranchRef,
        public string $Date,
        public string $Description,
        public string $Description_En,
        public int $FiscalYearRef,
        public int $LedgerRef,
        public int $VoucherTypeRef,
        public string $VoucherTypeOwnerSystem,
        public int $VoucherTypeCode,
        public int $Number,
        public string $AuxiliaryNumber,
        public bool $IsCurrentBased,
        public VoucherStateEnum $State,
        public bool $IsExternal,
        public int $Creator,
        public string $CreatorName,
        public string $StateTitle,
        public array $VoucherItemData,
    ) {}

    /**
     * @return array{
     *       BranchRef: int,
     *       Date: string,
     *       Description: string,
     *       Description_En: string,
     *       FiscalYearRef: int,
     *       LedgerRef: int,
     *       VoucherTypeRef: int,
     *       VoucherTypeOwnerSystem: string,
     *       VoucherTypeCode: int,
     *       Number: int,
     *       AuxiliaryNumber: string,
     *       IsCurrentBased: bool,
     *       State: VoucherStateEnum,
     *       IsExternal: bool,
     *       Creator: int,
     *       CreatorName: string,
     *       StateTitle: string,
     *      VoucherItemData: VoucherItemData
     *   }
     */
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
                fn(VoucherItemData $item) => $item->toArray(),
                $this->VoucherItemData
            ),
        ];
    }
}
