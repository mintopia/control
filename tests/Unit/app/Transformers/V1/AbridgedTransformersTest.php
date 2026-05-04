<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\User;
use App\Transformers\V1\AbridgedSeatTransformer;
use App\Transformers\V1\AbridgedTicketProviderTransformer;
use App\Transformers\V1\AbridgedTicketTypeTransformer;
use App\Transformers\V1\AbridgedUserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\TestCase;

class AbridgedTransformersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_transformer_exposes_id_nickname_name_email()
    {
        $user = User::factory()->withEmailAddress()->create([
            'nickname' => 'jess',
            'name' => 'Jess Smith',
        ]);

        $data = $this->transform(new AbridgedUserTransformer, $user);

        $this->assertEquals(
            ['id', 'nickname', 'name', 'email'],
            array_keys($data),
        );
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals('jess', $data['nickname']);
        $this->assertEquals('Jess Smith', $data['name']);
        $this->assertEquals($user->primaryEmail->email, $data['email']);
    }

    public function test_user_transformer_handles_user_without_primary_email()
    {
        $user = User::factory()->create();
        $data = $this->transform(new AbridgedUserTransformer, $user);
        $this->assertNull($data['email']);
    }

    public function test_seat_transformer_exposes_id_label_row_number()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create([
            'seating_plan_id' => $plan->id,
            'label' => 'A12',
            'row' => 'A',
            'number' => 12,
        ]);

        $data = $this->transform(new AbridgedSeatTransformer, $seat);

        $this->assertEquals(
            ['id', 'label', 'row', 'number'],
            array_keys($data),
        );
        $this->assertEquals('A12', $data['label']);
        $this->assertEquals('A', $data['row']);
        $this->assertEquals(12, $data['number']);
    }

    public function test_ticket_type_transformer_exposes_id_name()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id, 'name' => 'Standard']);

        $data = $this->transform(new AbridgedTicketTypeTransformer, $type);

        $this->assertEquals(['id', 'name'], array_keys($data));
        $this->assertEquals('Standard', $data['name']);
    }

    public function test_ticket_provider_transformer_exposes_id_code_name()
    {
        $provider = TicketProvider::factory()->create([
            'name' => 'Internal',
            'code' => 'internal',
        ]);

        $data = $this->transform(new AbridgedTicketProviderTransformer, $provider);

        $this->assertEquals(['id', 'code', 'name'], array_keys($data));
        $this->assertEquals('internal', $data['code']);
        $this->assertEquals('Internal', $data['name']);
    }

    protected function transform($transformer, $model): array
    {
        $manager = new Manager;
        $resource = new Item($model, $transformer);

        return $manager->createData($resource)->toArray()['data'];
    }
}
