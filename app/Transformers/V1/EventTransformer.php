<?php

namespace App\Transformers\V1;

use App\Models\Event;

class EventTransformer extends AbstractTransformer
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Event $event)
    {
        $data = [
            'code' => $event->code,
            'name' => $event->name,
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at->toIso8601String(),
            'seating_locked' => $event->seating_locked,
        ];
        return $this->modifyForUser($data, $event);
    }
}
