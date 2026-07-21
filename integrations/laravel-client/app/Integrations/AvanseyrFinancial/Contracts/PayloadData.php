<?php

namespace App\Integrations\AvanseyrFinancial\Contracts;

interface PayloadData
{
    public function toArray(): array;
}
