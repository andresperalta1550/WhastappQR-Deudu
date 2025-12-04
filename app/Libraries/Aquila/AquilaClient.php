<?php

namespace App\Libraries\Aquila;

use App\Libraries\Contracts\DebtorLookupInterface;
use Illuminate\Support\Facades\Log;

/**
 * AquilaClient is responsible for integrating with the "Aquila" service
 * to look up debtor information based on phone numbers.
 *
 * Aquila is an internal API service used for debtor information retrieval.
 */
class AquilaClient implements DebtorLookupInterface
{
    public function __construct()
    {
        $this->apiBaseUrl = config('services.aquila.api_base_url');
    }

    /**
     * Search for debtor information based on the provided phone number.
     *
     * @param string $phoneNumber The phone number to search for.
     * @return array|null An associative array containing debtor information if found, or null if not found.
     */
    public function search(string $phoneNumber): ?array
    {
        try {
            $url = "{$this->apiBaseUrl}/ia-caller/debtor-phone/1/{$phoneNumber}";

            $response = Http::get($url);

            if (!$response->successful()) {
                Log::error('AquilaClient search failed: ' . $response->body());
                return null;
            }

            return [
                'phone' => $response->json()['data']['phone'] ?? null,
                'source' => 'aquila',
                'debtor_id' => $response->json()['data']['debtor_id'] ?? null,
                'country_code' => $response->json()['data']['country_code'] ?? null,
                'city_code' => $response->json()['data']['city_code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('AquilaClient search error: ' . $e->getMessage());
            return null;
        }
    }
}
