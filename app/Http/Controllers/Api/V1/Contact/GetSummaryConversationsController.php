<?php

namespace App\Http\Controllers\Api\V1\Contact;

use App\Http\Controllers\Controller;
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

        // 1. Obtain all debtors that belong to this coordination
        $debtors = \App\Models\Debtor::where('coordinator_id', $coordinationId)->get();
        $debtorIds = $debtors->pluck('id')->toArray();

        // 2. Obtain all channels that belong to this coordination
        $channels = \App\Models\Channel::where('coordination_id', $coordinationId)->get();
        $channelPhoneNumbers = $channels->pluck('phone_number')->toArray();

        // 3. Build the query to filter contacts
        // If they have debtor_id in the list of debtors, or if they don't have debtor_id
        // but their remote_phone_number is in the channels of the coordination
        $contacts = \App\Models\Contact::query()
            ->where(function ($query) use ($debtorIds, $channelPhoneNumbers) {
                // Filter by debtor_id if it is in the list of debtors
                if (!empty($debtorIds)) {
                    $query->whereIn('debtor_id', $debtorIds);
                }

                // Or filter by remote_phone_number if it doesn't have debtor_id
                if (!empty($channelPhoneNumbers)) {
                    $query->orWhere(function ($subQuery) use ($channelPhoneNumbers) {
                        $subQuery->whereNull('debtor_id')
                            ->whereIn('channel_phone_number', $channelPhoneNumbers);
                    });
                }
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $contacts->items(),
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
                'last_page' => $contacts->lastPage(),
                'from' => $contacts->firstItem(),
                'to' => $contacts->lastItem(),
            ]
        ], 200);
    }
}
