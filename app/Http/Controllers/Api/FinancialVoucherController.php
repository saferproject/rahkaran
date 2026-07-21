<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePartyRequest;
use App\Http\Requests\RegisterDLRequest;
use App\Http\Requests\RegisterVoucherRequest;
use App\Services\FinancialVoucherService;
use Illuminate\Http\JsonResponse;

class FinancialVoucherController extends Controller
{
    public function __construct(
        private readonly FinancialVoucherService $service,
    ) {}

    public function registerVoucher(RegisterVoucherRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->register_voucher($request->toDto())
        );
    }

    public function registerDL(RegisterDLRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->register_dl($request->toDto())
        );
    }

    public function generateParty(GeneratePartyRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->generate_party($request->toDto())
        );
    }
}
