<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Transformers\V1\EventTransformer;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::whereDraft(false)->get();
        return fractal($events, new EventTransformer())->respond();
    }

    public function show(Event $event)
    {
        return fractal($event, new EventTransformer())->respond();
    }
}
