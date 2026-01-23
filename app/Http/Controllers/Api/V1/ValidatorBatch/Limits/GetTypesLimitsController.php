<?php

namespace App\Http\Controllers\Api\V1\ValidatorBatch\Limits;

use App\Http\Controllers\Controller;
use App\Models\LimitsValidatorBatch;

class GetTypesLimitsController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'data' => LimitsValidatorBatch::TYPES
        ]);
    }
}
