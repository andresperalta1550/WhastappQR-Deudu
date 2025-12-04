<?php

namespace App\Libraries\Oracle;

use App\Libraries\Contracts\DebtorLookupInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OracleClient is responsible for integrating with the "Oraculo" service
 * to look up debtor information based on phone numbers.
 *
 * Oracle is a internal API service used for debtor information retrieval.
 */
class OracleClient implements DebtorLookupInterface
{
    public function __construct()
    {
        $this->apiBaseUrl = config('services.oracle.api_base_url');
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
            $url = "{$this->apiBaseUrl}/contacts-phones?phone=" . urlencode($phoneNumber);

            $response = Http::get($url);

            if (!$response->successful()) {
                Log::error('OracleClient search failed: ' . $response->body());
                return null;
            }

            return [
                'phone' => $response->json()["data"][0]['phone'] ?? null,
                'source' => 'oracle',
                'debtor_id' => $response->json()["data"][0]['debtor_id'] ?? null,
                'country_code' => $response->json()["data"][0]['country_code'] ?? null,
                'city_code' => $response->json()["data"][0]['city_code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OracleClient search error: ' . $e->getMessage());
            return null;
        }
    }
}
