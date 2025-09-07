<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\TicketImportRequest;
use Tests\TestCase;

class TicketImportRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketImportRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsCsv()
    {
        $request = new TicketImportRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('csv', $rules);
    }

    public function testRulesReturnsArray()
    {
        $request = new TicketImportRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testCsvRuleContainsFileAndMimetypes()
    {
        $request = new TicketImportRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('csv', $rules);

        $csvRule = $rules['csv'];
        $csvRuleString = is_array($csvRule) ? implode('|', $csvRule) : $csvRule;

        $this->assertStringContainsString('file', $csvRuleString);
        $this->assertTrue(
            str_contains($csvRuleString, 'mimetypes:') || str_contains($csvRuleString, 'mimes:')
        );
    }

    public function testRulesDoesNotContainUnexpectedFields()
    {
        $request = new TicketImportRequest();
        $rules = $request->rules();
        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('csv', $rules);
    }

    public function testAuthorizeAlwaysTrue()
    {
        $request = new TicketImportRequest();
        $this->assertTrue($request->authorize());
    }
}
