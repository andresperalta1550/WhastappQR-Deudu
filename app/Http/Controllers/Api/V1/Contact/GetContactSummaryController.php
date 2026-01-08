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

        // Process debtor field filters (from MySQL)
        $filters = $this->processDebtorFilters($filters);

        // Check if coordination_id filter exists
        $coordinationId = $this->extractCoordinationId($filters);

        if ($coordinationId !== null) {
            // Apply special logic for coordination_id
            $matchConditions = $this->buildCoordinationMatchConditions($coordinationId, $filters, $mongoFilterService);
        } else {
            // Build MongoDB match conditions from filters
            $matchConditions = $mongoFilterService->buildMatchConditions($filters);
        }

        // Use aggregation to group by debtor_id (last contact per debtor)
        return $this->getGroupedByDebtor($matchConditions, $perPage, $page);
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

        // Get other filters (excluding coordination_id)
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
     * @param array $matchConditions
     * @param int $perPage
     * @param int $page
     * @return JsonResponse
     */
    protected function getGroupedByDebtor(array $matchConditions, int $perPage, int $page): JsonResponse
    {
        $skip = ($page - 1) * $perPage;

        // Pipeline for aggregation
        $pipeline = [];

        // Only add match stage if there are conditions
        if (!empty($matchConditions)) {
            $pipeline[] = ['$match' => $matchConditions];
        }

        // Order by updated_at desc
        $pipeline[] = ['$sort' => ['updated_at' => -1]];

        // Group by debtor_id (get last contact for each debtor)
        $pipeline[] = [
            '$group' => [
                '_id' => '$debtor_id',
                'contact' => ['$first' => '$$ROOT']
            ]
        ];

        // Remove group metadata
        $pipeline[] = ['$replaceRoot' => ['newRoot' => '$contact']];

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
        $data = $this->enrichWithDebtorAndCoordinationInfo($data);

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
     * Enrich contact data with debtor information, coordination_id, and coordination_fullname.
     *
     * @param array $contacts
     * @return array
     */
    protected function enrichWithDebtorAndCoordinationInfo(array $contacts): array
    {
        if (empty($contacts)) {
            return $contacts;
        }

        // Collect all unique IDs and phone numbers in one pass
        $debtorIds = [];
        $channelPhoneNumbers = [];

        foreach ($contacts as $contact) {
            if (isset($contact['debtor_id']) && $contact['debtor_id'] !== null) {
                $debtorIds[$contact['debtor_id']] = true;
            }
            if (isset($contact['channel_phone_number']) && $contact['channel_phone_number'] !== null) {
                $channelPhoneNumbers[$contact['channel_phone_number']] = true;
            }
        }

        // Fetch all necessary data in parallel
        $debtors = !empty($debtorIds)
            ? \App\Models\Debtor::whereIn('id', array_keys($debtorIds))->get()->keyBy('id')
            : collect();

        $channels = !empty($channelPhoneNumbers)
            ? \App\Models\Channel::whereIn('phone_number', array_keys($channelPhoneNumbers))->get()->keyBy('phone_number')
            : collect();

        // Extract coordination_ids from channels
        $coordinationIds = $channels
            ->pluck('coordination_id')
            ->filter()
            ->unique()
            ->toArray();

        // Fetch coordinators
        $coordinators = !empty($coordinationIds)
            ? \App\Models\User::whereIn('id', $coordinationIds)->get()->keyBy('id')
            : collect();

        // Single pass enrichment
        foreach ($contacts as &$contact) {
            // Enrich debtor information
            $debtorId = $contact['debtor_id'] ?? null;
            if ($debtorId && $debtors->has($debtorId)) {
                $debtor = $debtors[$debtorId];
                $contact['debtor_fullname'] = $debtor->getFullname();
                $contact['debtor_identification'] = $debtor->identification ?? null;
            } else {
                $contact['debtor_fullname'] = null;
                $contact['debtor_identification'] = null;
            }

            // Enrich coordination information
            $channelPhoneNumber = $contact['channel_phone_number'] ?? null;
            if ($channelPhoneNumber && $channels->has($channelPhoneNumber)) {
                $channel = $channels[$channelPhoneNumber];
                $coordinationId = $channel->getCoordinationId();
                $contact['coordination_id'] = $coordinationId;

                // Add coordination_fullname
                if ($coordinationId && $coordinators->has($coordinationId)) {
                    $contact['coordination_fullname'] = $coordinators[$coordinationId]->getFullname();
                } else {
                    $contact['coordination_fullname'] = null;
                }
            } else {
                $contact['coordination_id'] = null;
                $contact['coordination_fullname'] = null;
            }
        }

        return $contacts;
    }
}
