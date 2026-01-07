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
}
