<?php

namespace App\Http\Controllers\Api\V1\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\GetContactsByDebtorsRequest;
use App\Models\Contact;
use Illuminate\Http\Request;

class GetSummaryByDebtorsController extends Controller
{
    public function __invoke(GetContactsByDebtorsRequest $request): \Illuminate\Http\JsonResponse
    {
        // Obtener los debtor IDs procesados desde el request
        $debtorIds = $request->getDebtorIds();
        $perPage = $request->getPerPage();

        // Consultar contactos con paginación
        $contacts = Contact::whereIn('debtor_id', $debtorIds)
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
