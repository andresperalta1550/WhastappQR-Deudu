<?php

namespace App\Http\Controllers\Api\V1\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\GetContactSummaryRequest;
use App\Models\Contact;
use App\Services\MongoFilterService;
use Illuminate\Http\JsonResponse;

class GetContactSummaryController extends Controller
{
    /**
     * Get summary of contacts with flexible filtering.
     *
     * This unified endpoint replaces both GetSummaryByDebtorsController and 
     * GetSummaryConversationsController. It supports:
     * - Flexible filtering via the global filter structure
     * - Optional grouping by debtor_id to get the last contact per debtor
     * - Pagination
     *
     * @param GetContactSummaryRequest $request
     * @param MongoFilterService $mongoFilterService
     * @return JsonResponse
     */
    public function __invoke(
        GetContactSummaryRequest $request,
        MongoFilterService $mongoFilterService
    ): JsonResponse {
        $filters = $request->getFilters();
        $perPage = $request->getPerPage();
        $page = $request->getPage();

        // process debtor filters first (they map to debtor_id)
        $filters = $this->processDebtorFilters($filters);

        // Split filters into Identity (Pre-Match) and State (Post-Match)
        [$identityFilters, $stateFilters] = $this->splitFilters($filters);

        // Check if coordination_id filter exists (it's an identity filter)
        $coordinationId = $this->extractCoordinationId($identityFilters);

        if ($coordinationId !== null) {
            // Apply special logic for coordination_id to Pre-Match
            $preMatchConditions = $this->buildCoordinationMatchConditions($coordinationId, $identityFilters, $mongoFilterService);
        } else {
            // Build MongoDB match conditions from identity filters
            $preMatchConditions = $mongoFilterService->buildMatchConditions($identityFilters);
        }

        // Build Post-Match conditions from state filters
        $postMatchConditions = $mongoFilterService->buildMatchConditions($stateFilters);

        // Use aggregation to group by debtor_id (last contact per debtor)
        return $this->getGroupedByDebtor($preMatchConditions, $postMatchConditions, $perPage, $page);
    }

    /**
     * Split filters into Identity (Pre-Aggregation) and State (Post-Aggregation).
     * We identify if the filter is an identity filter by checking if it is a debtor_id, 
     * channel_phone_number, remote_phone_number, coordination_id, debtor_fullname, 
     * or debtor_identification. 
     *
     * @param \App\ValueObjects\FilterCollection $filters
     * @return array{0: \App\ValueObjects\FilterCollection, 1: \App\ValueObjects\FilterCollection}
     */
    protected function splitFilters(\App\ValueObjects\FilterCollection $filters): array
    {
        $identityFields = [
            'debtor_id',
            'channel_phone_number',
            'remote_phone_number',
            'coordination_id',
            // debtor_fullname and debtor_identification are converted to debtor_id by processDebtorFilters
        ];

        $identityFilters = new \App\ValueObjects\FilterCollection();
        $stateFilters = new \App\ValueObjects\FilterCollection();

        foreach ($filters->all() as $filter) {
            if (in_array($filter->getField(), $identityFields)) {
                $identityFilters->add($filter);
            } else {
                $stateFilters->add($filter);
            }
        }

        return [$identityFilters, $stateFilters];
    }

    /**
     * Process filters for debtor fields (MySQL) and convert to debtor_id filter.
     *
     * @param \App\ValueObjects\FilterCollection $filters
     * @return \App\ValueObjects\FilterCollection
     */
    protected function processDebtorFilters($filters): \App\ValueObjects\FilterCollection
    {
        $debtorFilters = [];
        $otherFilters = new \App\ValueObjects\FilterCollection();
        $debtorFieldMap = [
            'debtor_fullname' => 'fullname',
            'debtor_identification' => 'identification',
        ];

        // Separate debtor filters from other filters
        foreach ($filters->all() as $filter) {
            if (isset($debtorFieldMap[$filter->getField()])) {
                $debtorFilters[] = $filter;
            } else {
                $otherFilters->add($filter);
            }
        }

        // If there are no debtor filters, return original filters
        if (empty($debtorFilters)) {
            return $filters;
        }

        // Query MySQL Debtors table with debtor filters
        $debtorQuery = \App\Models\Debtor::query();

        foreach ($debtorFilters as $filter) {
            $field = $filter->getField();
            $operator = $filter->getOperator();
            $value = $filter->getValue();

            // Apply filter based on field and operator
            if ($field === 'debtor_fullname') {
                // For fullname, we need to search in concatenated name + lastname
                $this->applyFullnameFilter($debtorQuery, $operator, $value);
            } elseif ($field === 'debtor_identification') {
                $this->applyMySQLFilter($debtorQuery, 'identification', $operator, $value);
            }
        }

        // Get debtor IDs that match the filters
        $debtorIds = $debtorQuery->pluck('id')->toArray();

        // If no debtors match, return empty result by adding impossible filter
        if (empty($debtorIds)) {
            $otherFilters->add(new \App\ValueObjects\Filter('debtor_id', 'IN', []));
        } else {
            // Add debtor_id IN filter with found IDs
            $otherFilters->add(new \App\ValueObjects\Filter('debtor_id', 'IN', $debtorIds));
        }

        return $otherFilters;
    }

    /**
     * Apply fullname filter to debtor query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $operator
     * @param mixed $value
     * @return void
     */
    protected function applyFullnameFilter($query, string $operator, $value): void
    {
        if ($operator === 'LIKE') {
            $query->where(function ($q) use ($value) {
                $q->whereRaw("CONCAT(name, ' ', lastname) LIKE ?", ["%{$value}%"]);
            });
        } elseif ($operator === 'EQUAL') {
            $query->where(function ($q) use ($value) {
                $q->whereRaw("CONCAT(name, ' ', lastname) = ?", [$value]);
            });
        }
        // Add more operators as needed
    }

    /**
     * Apply filter to MySQL query based on operator.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return void
     */
    protected function applyMySQLFilter($query, string $field, string $operator, $value): void
    {
        match ($operator) {
            'EQUAL' => $query->where($field, '=', $value),
            'NOT_EQUAL' => $query->where($field, '!=', $value),
            'LIKE' => $query->where($field, 'LIKE', "%{$value}%"),
            'NOT_LIKE' => $query->where($field, 'NOT LIKE', "%{$value}%"),
            'IN' => $query->whereIn($field, $value),
            'NOT_IN' => $query->whereNotIn($field, $value),
            'GREATER_THAN' => $query->where($field, '>', $value),
            'GREATER_THAN_OR_EQUAL' => $query->where($field, '>=', $value),
            'LESS_THAN' => $query->where($field, '<', $value),
            'LESS_THAN_OR_EQUAL' => $query->where($field, '<=', $value),
            'IS_NULL' => $query->whereNull($field),
            'IS_NOT_NULL' => $query->whereNotNull($field),
            default => null
        };
    }


    /**
     * Extract coordination_id value from filters if present.
     *
     * @param \App\ValueObjects\FilterCollection $filters
     * @return int|null
     */
    protected function extractCoordinationId($filters): ?int
    {
        foreach ($filters->all() as $filter) {
            if ($filter->getField() === 'coordination_id' && $filter->getOperator() === 'EQUAL') {
                return (int) $filter->getValue();
            }
        }

        return null;
    }

    /**
     * Build match conditions for coordination_id filter.
     *
     * @param int $coordinationId
     * @param \App\ValueObjects\FilterCollection $filters
     * @param MongoFilterService $mongoFilterService
     * @return array
     */
    protected function buildCoordinationMatchConditions(int $coordinationId, $filters, MongoFilterService $mongoFilterService): array
    {
        // 1. Obtain debtors
        $debtorIds = \App\Models\Debtor::where('coordinator_id', $coordinationId)
            ->pluck('id')
            ->toArray();

        // 2. Obtain channels
        $channelPhoneNumbers = \App\Models\Channel::where('coordination_id', $coordinationId)
            ->pluck('phone_number')
            ->toArray();

        // Build $or conditions
        $orConditions = [
            '$or' => [
                ['debtor_id' => ['$in' => $debtorIds]],
                [
                    'debtor_id' => null,
                    'channel_phone_number' => ['$in' => $channelPhoneNumbers]
                ]
            ]
        ];

        // For coordination match, we primarily care about building the OR structure for identity
        // The $filters passed here should properly be just the identity filters minus coordination_id
        $otherFilters = new \App\ValueObjects\FilterCollection();
        foreach ($filters->all() as $filter) {
            if ($filter->getField() !== 'coordination_id') {
                $otherFilters->add($filter);
            }
        }

        // Build conditions for other filters
        $otherConditions = $mongoFilterService->buildMatchConditions($otherFilters);

        // Merge conditions
        if (!empty($otherConditions)) {
            return array_merge($orConditions, $otherConditions);
        }

        return $orConditions;
    }


    /**
     * Get contacts grouped by debtor_id (last contact per debtor).
     *
     * @param array $preMatchConditions
     * @param array $postMatchConditions
     * @param int $perPage
     * @param int $page
     * @return JsonResponse
     */
    protected function getGroupedByDebtor(array $preMatchConditions, array $postMatchConditions, int $perPage, int $page): JsonResponse
    {
        $skip = ($page - 1) * $perPage;

        // Pipeline for aggregation
        $pipeline = [];

        // 1. Pre-Match (Identity filters) - Narrow down the set of candidates efficiently
        // Also exclude soft-deleted records since we are using raw aggregation
        if (!empty($preMatchConditions)) {
            $pipeline[] = ['$match' => array_merge($preMatchConditions, ['deleted_at' => null])];
        } else {
            $pipeline[] = ['$match' => ['deleted_at' => null]];
        }

        // 2. Sort by updated_at desc to ensure "first" is the latest
        $pipeline[] = ['$sort' => ['updated_at' => -1]];

        // 3. Group by debtor_id to get the single latest contact state per debtor
        $pipeline[] = [
            '$group' => [
                '_id' => [
                    'debtor_id' => '$debtor_id',
                    // Note: If debtor_id is null, we might want to split by something else or keep them separate?
                    // Currently, if debtor_id is null, they all group together into one null bucket which might not be desired
                    // if they are different unknown users.
                    // Ideally, if debtor_id is missing, we group by remote_phone_number + channel_phone_number.
                    // For now, retaining existing behavior but checking if we need composite key.
                ],
                // We actually want to group by debtor_id if present, OR unique contact if not?
                // The prompt implies "debtor" context.
                // Existing logic was just '_id' => '$debtor_id'.
                // If multiple contacts have null debtor_id, they will collapse.
                // Let's stick to the request: "Latest data".
                '_id' => '$debtor_id', // Preserving existing grouping key
                'contact' => ['$first' => '$$ROOT']
            ]
        ];

        // 4. Unwrap the contact
        $pipeline[] = ['$replaceRoot' => ['newRoot' => '$contact']];

        // 5. Post-Match (State filters) - Apply to the LATEST record found
        if (!empty($postMatchConditions)) {
            $pipeline[] = ['$match' => $postMatchConditions];
        }


        // Pagination facet
        $pipeline[] = [
            '$facet' => [
                'metadata' => [
                    ['$count' => 'total']
                ],
                'data' => [
                    ['$sort' => ['updated_at' => -1]],
                    ['$skip' => $skip],
                    ['$limit' => $perPage]
                ]
            ]
        ];

        // Execute aggregation
        $result = (new Contact())->raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $result = iterator_to_array($result)[0];

        $total = $result['metadata'][0]['total'] ?? 0;
        $data = $result['data'];

        // Enrich data with debtor information
        $data = $this->enrichWithDebtorInfo($data);

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total > 0 ? $skip + 1 : null,
                'to' => $skip + count($data),
            ]
        ], 200);
    }

    /**
     * Enrich contact data with debtor information.
     *
     * @param array $contacts
     * @return array
     */
    protected function enrichWithDebtorInfo(array $contacts): array
    {
        if (empty($contacts)) {
            return $contacts;
        }

        // Collect all unique IDs and phone numbers in one pass
        $debtorIds = [];

        foreach ($contacts as $contact) {
            if (isset($contact['debtor_id']) && $contact['debtor_id'] !== null) {
                $debtorIds[$contact['debtor_id']] = true;
            }
        }

        // Fetch all necessary data in parallel
        $debtors = !empty($debtorIds)
            ? \App\Models\Debtor::whereIn('id', array_keys($debtorIds))->get()->keyBy('id')
            : collect();

        // Single pass enrichment
        foreach ($contacts as &$contact) {
            // Enrich debtor information
            $debtorId = $contact['debtor_id'] ?? null;
            if ($debtorId && $debtors->has($debtorId)) {
                $debtor = $debtors[$debtorId];
                $contact['debtor_fullname'] = $debtor->getFullname();
                $contact['debtor_identification'] = $debtor->identification;
            } else {
                $contact['debtor_fullname'] = null;
                $contact['debtor_identification'] = null;
            }
        }

        return $contacts;
    }
}
