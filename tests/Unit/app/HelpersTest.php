<?php

namespace Tests\Unit\app;

use PHPUnit\Framework\TestCase;

use function app\makeCode;
use function app\makePermalink;

class HelpersTest extends TestCase
{
    public function test_makePermalink_basic()
    {
        $this->assertEquals('hello-world', makePermalink('Hello World'));
    }

    public function test_makePermalink_removes_special_characters()
    {
        $this->assertEquals('abc-123', makePermalink('ABC!@# 123'));
    }

    public function test_makePermalink_truncates_to_128_chars()
    {
        $input = str_repeat('a', 130);
        $output = makePermalink($input);
        $this->assertEquals(128, strlen($output));
    }

    public function test_makeCode_default_length()
    {
        $code = makeCode();
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code);
    }

    public function test_makeCode_custom_length()
    {
        $code = makeCode(10);
        $this->assertEquals(10, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{10}$/', $code);
    }
}
