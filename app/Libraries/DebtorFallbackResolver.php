<?php

namespace App\Libraries;

use App\Models\Message;

class DebtorFallbackResolver
{
    /**
     * @var DebtorLookupInterface[] $providers The list of debtor lookup services
     */
    protected array $providers = [];

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * We use this function to search for debtor information in our local database
     * based on the message UUID.
     *
     * @param string $phoneNumber
     * @param string|null $messageUuid
     * @return array|null
     */
    private function searchLocal(
        string $phoneNumber,
        ?string $messageUuid
    ): ?array
    {
        $message = (new \App\Models\Message)
            ->where('message_uuid', $messageUuid)
            ->first();

        if (!$message) return null;
        if (!$message->getDebtorId()) return null;

        return [
            'phone' => $phoneNumber,
            'source' => 'local',
            'debtor_id' => $message->getDebtorId(),
            'country_code' => null,
            'city_code' => null,
        ];
    }

    /**
     * Search for debtor information using the configured providers in sequence.
     *
     * @param string $phoneNumber The phone number to search for.
     * @return array|null An associative array containing debtor information if found, or null if not
     * found.
     */
    public function resolve(string $phoneNumber, ?string $messageUuid): ?array
    {
        $localResult = $this->searchLocal($phoneNumber, $messageUuid);
        if ($localResult !== null) {
            return $localResult;
        }
        foreach ($this->providers as $provider) {
            $result = $provider->search($phoneNumber);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
