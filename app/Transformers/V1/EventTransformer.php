<?php

namespace App\Transformers\V1;

use App\Models\Event;

class EventTransformer extends AbstractTransformer
{
    /**
     * @var array<int, string>
     */
    protected array $defaultIncludes = [];

    /**
     * @var array<int, string>
     */
    protected array $availableIncludes = [];

    public function transform(Event $event)
    {
        $data = [
            'code' => $event->code,
            'name' => $event->name,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'seating_locked' => $event->seating_locked,
        ];

        return $this->modifyForUser($data, $event);
    }

    protected function getAdminProperties(object $object): array
    {
        /** @var Event $object */
        return [
            'draft' => (bool) $object->draft,
            'boxoffice_url' => $object->boxoffice_url,
            'seating_opens_at' => $object->seating_opens_at?->toIso8601String(),
            'seating_closes_at' => $object->seating_closes_at?->toIso8601String(),
        ];
    }
}
