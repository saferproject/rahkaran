<?php

namespace App\DTO;

class RegisterDLData
{
    public function __construct(
        public string $Code,
        public int $DLTypeRef,
        public string $Description,
        public int $ID,
        public int $ReferenceID,
        public string $Title,
        public string $Title_En,
    ) {}

    /**
     * @return array{
     *      Code: string,
     *      DLTypeRef: int,
     *      Description: string,
     *      ID: int,
     *      ReferenceID: int,
     *      Title: string,
     *      Title_En: string,
     *  }
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
