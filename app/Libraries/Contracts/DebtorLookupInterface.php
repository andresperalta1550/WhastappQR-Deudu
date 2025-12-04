<?php

namespace App\Libraries\Contracts;

/**
 * That defines the interface for debtor lookup services.
 *
 * @package App\Libraries\Contracts
 */
interface DebtorLookupInterface
{
    /**
     * Search for debtor information based on the provided phone number.
     *
     * @param string $phoneNumber The phone number to search for.
     * @return array|null An associative array containing debtor information if found, or null if not found.
     */
    public function search(string $phoneNumber): ?array;
}
