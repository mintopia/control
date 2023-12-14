<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\SeatingPlan;
use App\Transformers\V1\SeatingPlanTransformer;
use Illuminate\Http\Request;

class SeatingPlanController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $query = $event->seatingPlans();

        $params = [
            'perPage' => $request->input('perPage', 20),
        ];

        $plans = $query->paginate($params['perPage'])->appends($params);
        return fractal($plans, new SeatingPlanTransformer())->respond();
    }

    public function show(SeatingPlan $seatingplan)
    {
        return fractal($seatingplan, new SeatingPlanTransformer())->respond();
    }
}
