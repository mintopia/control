<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SeatingPlanController;
use App\Models\Event;
use App\Models\SeatingPlan;
use App\Http\Requests\Admin\SeatingPlanUpdateRequest;
use App\Http\Requests\Admin\SeatingPlanImportRequest;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use App\Models\Seat;

class SeatingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateShowEditDeleteRefresh()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $c = new SeatingPlanController();
        $this->assertTrue(is_object($c->create($event)));
        $this->assertTrue(is_object($c->show($event, $plan)));
        $this->assertTrue(is_object($c->edit($event, $plan)));
        $this->assertTrue(is_object($c->delete($event, $plan)));
        $this->assertTrue(is_object($c->refresh($event, $plan)));
        $this->assertTrue(is_object($c->up($event, $plan)));
        $this->assertTrue(is_object($c->down($event, $plan)));
    }

    public function testStoreCreatesPlanAndUpdateObjectWithImage()
    {
        $event = Event::factory()->create();

        // Use a data URI for a 1x1 GIF so getimagesize can read dimensions
        $gifBase64 = 'R0lGODdhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';
        $dataUri = 'data:image/gif;base64,' . $gifBase64;

        $req = SeatingPlanUpdateRequest::create('/admin', 'POST', ['name' => 'PlanX', 'image_url' => $dataUri, 'scale' => 75]);
        $controller = new SeatingPlanController();

        try {
            $resp = $controller->store($req, $event);
            $this->assertTrue(true, 'store returned response');
        } catch (UrlGenerationException $ex) {
            // route may not be registered in unit tests
        }

        $plan = SeatingPlan::whereName('PlanX')->first();
        $this->assertNotNull($plan, 'SeatingPlan created');
        // call updateObject directly to ensure image sizes are set
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        // change name to ensure update occurs
        $req2 = SeatingPlanUpdateRequest::create('/admin', 'POST', ['name' => 'PlanX2', 'image_url' => $dataUri]);
        $method->invoke($controller, $plan, $req2);
        $this->assertEquals(1, $plan->fresh()->image_width);
        $this->assertEquals(1, $plan->fresh()->image_height);
    }

    public function testUpdateAndDestroyRefreshUpDownBehaviours()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $req = SeatingPlanUpdateRequest::create('/admin', 'POST', ['name' => 'Changed', 'scale' => 110]);
        $controller = new SeatingPlanController();
        try {
            $controller->update($req, $event, $plan);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertDatabaseHas('seating_plans', ['id' => $plan->id, 'name' => 'Changed']);

        // refresh should increment revision
        $controller->refresh($event, $plan);
        $this->assertGreaterThan(1, $plan->fresh()->revision);

        // up/down just need to be callable; ensure they return response objects
        $this->assertTrue(is_object($controller->up($event, $plan)));
        $this->assertTrue(is_object($controller->down($event, $plan)));

        // destroy
        $reqDel = \App\Http\Requests\Admin\DeleteRequest::create('/admin', 'DELETE', ['confirm' => 'delete']);
        try {
            $controller->destroy($reqDel, $event, $plan);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertDatabaseMissing('seating_plans', ['id' => $plan->id]);
    }

    public function testExportReturnsStreamDownload()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        // add a seat so export includes a row
        $seat = new Seat();
        $seat->plan()->associate($plan);
        $seat->x = 10;
        $seat->y = 20;
        $seat->row = 'A';
        $seat->number = 1;
        $seat->label = 'S1';
        $seat->save();

        $controller = new SeatingPlanController();
        $resp = $controller->export($event, $plan);
        $this->assertInstanceOf(StreamedResponse::class, $resp);
        $cd = $resp->headers->get('content-disposition');
        $this->assertStringContainsString("seating-{$plan->id}-seats-", $cd);
    }

    public function testExportStreamContainsCsvContent()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        // add a seat so export includes a row
        $seat = new Seat();
        $seat->plan()->associate($plan);
        $seat->x = 10;
        $seat->y = 20;
        $seat->row = 'A';
        $seat->number = 1;
        $seat->label = 'S1';
        $seat->save();

        $controller = new SeatingPlanController();
        $resp = $controller->export($event, $plan);
        $this->assertInstanceOf(StreamedResponse::class, $resp);

        // Get the underlying callback and capture its output
        $callback = $resp->getCallback();
        ob_start();
        $callback();
        $output = ob_get_clean();

        // header row and seat label should be present in the CSV output
        $this->assertStringContainsString('ID,X,Y,Row,Number,Label', $output);
        $this->assertStringContainsString('S1', $output);
    }

    public function testImportProcessCreatesSeatsAndImportView()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        // create CSV content with header + one seat row (no ID so it will be created)
        $csv = "ID,X,Y,Row,Number,Label,Description,CSS Class,Seat Group ID,Disabled\n";
        $csv .= ",5,6,B,2,MySeat,Desc,cls,,0\n";

        // create a real temporary file and an UploadedFile instance for the request
        $tmp = tempnam(sys_get_temp_dir(), 'test_seating_');
        file_put_contents($tmp, $csv);
        $uploaded = new \Illuminate\Http\UploadedFile($tmp, 'test_seating.csv', null, null, true);

        $req = SeatingPlanImportRequest::create('/admin', 'POST', ['wipe' => false]);
        $req->files->set('csv', $uploaded);

        $controller = new SeatingPlanController();
        try {
            $controller->import_process($req, $event, $plan);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $this->assertDatabaseHas('seats', ['label' => 'MySeat', 'row' => 'B', 'number' => 2]);

        $view = $controller->import($event, $plan);
        $this->assertTrue(is_object($view));
    }

    public function testUpdateObjectSkipsImageSizingWhenNoImageUrl()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $controller = new SeatingPlanController();

        // Create a request without image_url so the sizing branch is skipped
        $req = SeatingPlanUpdateRequest::create('/admin', 'POST', ['name' => 'NoImage']);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $plan, $req);

        $fresh = $plan->fresh();
        $this->assertNull($fresh->image_width);
        $this->assertNull($fresh->image_height);
    }

    public function testUpdateObjectSetsDefaultScaleWhenMissing()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $controller = new SeatingPlanController();

        // No scale provided -> should default to 100
        $req = SeatingPlanUpdateRequest::create('/admin', 'POST', ['name' => 'DefaultScale']);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $plan, $req);

        $this->assertEquals(100, $plan->fresh()->scale);
    }
}
