<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExchangeRateRequest;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Get the available catalog of currencies.
     */
    public function availableCurrencies(): JsonResponse
    {
        return response()->json($this->exchangeRateService->getAvailableCurrencies());
    }

    /**
     * Get the history of exchange rate changes.
     */
    public function history(): JsonResponse
    {
        return response()->json($this->exchangeRateService->getHistory());
    }

    /**
     * Get all currently saved exchange rates.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->exchangeRateService->getAllRates());
    }

    /**
     * Store or update an exchange rate manually.
     */
    public function store(StoreExchangeRateRequest $request): JsonResponse
    {
        $rate = $this->exchangeRateService->storeRate($request->validated());

        return response()->json($rate);
    }

    /**
     * Fetch from live ER-API, cache it, and save the catalog.
     */
    public function fetchLive(): JsonResponse
    {
        try {
            $updated = $this->exchangeRateService->fetchAndStoreLiveRates();
            
            return response()->json([
                'message' => 'Rates updated successfully from live API',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove an exchange rate from the system.
     */
    public function destroy($currency): JsonResponse
    {
        $this->exchangeRateService->deleteRate($currency);
        return response()->json(['message' => 'Currency deactivated successfully']);
    }
}
