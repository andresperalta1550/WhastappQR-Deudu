<?php

namespace App\Http\Controllers\Api\V1\Contact;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class MarkAsResolvedController extends Controller
{
    /**
     * Mark a contact as resolved.
     *
     * @param Contact $contact
     * @return JsonResponse
     */
    public function __invoke(Contact $contact): JsonResponse
    {
        // Mark the contact as resolved
        $contact->setIsResolved(true);
        $contact->save();

        return response()->json([
            'message' => 'El contacto ha sido marcado como resuelto',
            'data' => $contact
        ], 200);
    }
}
