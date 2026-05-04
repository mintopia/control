<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Event;
use App\Transformers\V1\EventTransformer;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);

        $events = Event::query()
            ->orderBy('starts_at', 'desc')
            ->paginate($perPage)
            ->appends(['perPage' => $perPage]);

        return fractal($events, new EventTransformer(null, $this->apiKey($request)))->respond();
    }

    public function show(Request $request, Event $event)
    {
        return fractal($event, new EventTransformer(null, $this->apiKey($request)))->respond();
    }

    protected function apiKey(Request $request): ?ApiKey
    {
        $user = $request->user();

        return $user instanceof ApiKey ? $user : null;
    }
}
