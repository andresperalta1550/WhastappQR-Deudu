<?php

namespace App\Http\Controllers\Api\V1\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class GetSummaryConversationsController extends Controller
{
    /**
     * Get summary of conversations filtered by coordination.
     *
     * This endpoint retrieves contacts associated with a specific coordination ID.
     * It filters contacts by:
     * 1. Debtor IDs that belong to the coordination
     * 2. Remote phone numbers from channels associated with the coordination
     *
     * @param \App\Http\Requests\Contact\GetSummaryConversationsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(\App\Http\Requests\Contact\GetSummaryConversationsRequest $request): \Illuminate\Http\JsonResponse
    {
        $coordinationId = $request->getCoordinationId();
        $perPage = $request->getPerPage();
        $page = max(1, (int) $request->get('page', 1));

        // 1. Obtain debtors
        $debtorIds = \App\Models\Debtor::where('coordinator_id', $coordinationId)
            ->pluck('id')
            ->toArray();

        // 2. Obtain channels
        $channelPhoneNumbers = \App\Models\Channel::where('coordination_id', $coordinationId)
            ->pluck('phone_number')
            ->toArray();

        $matchConditions = [
            '$or' => [
                ['debtor_id' => ['$in' => $debtorIds]],
                [
                    'debtor_id' => null,
                    'channel_phone_number' => ['$in' => $channelPhoneNumbers]
                ]
            ]
        ];

        // Filter by is_resolved if provided
        $isResolved = $request->getIsResolved();
        if ($isResolved !== null) {
            $matchConditions['is_resolved'] = $isResolved;
        }

        $matchStage = [
            '$match' => $matchConditions
        ];

        // Pagination skip
        $skip = ($page - 1) * $perPage;

        // Pipeline
        $pipeline = [
            $matchStage,

            // Order by updated_at desc
            ['$sort' => ['updated_at' => -1]],

            // Group by debtor_id (last contact)
            [
                '$group' => [
                    '_id' => '$debtor_id',
                    'contact' => ['$first' => '$$ROOT']
                ]
            ],

            // Remove group metadata
            ['$replaceRoot' => ['newRoot' => '$contact']],

            // Pagination
            [
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
            ]
        ];

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
