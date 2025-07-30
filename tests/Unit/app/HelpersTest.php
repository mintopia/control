<?php

namespace Tests\Unit\app\Helpers;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_makePermalink_basic()
    {
        $this->assertEquals('hello-world', \app\makePermalink('Hello World'));
    }

    public function test_makePermalink_removes_special_characters()
    {
        $this->assertEquals('abc-123', \app\makePermalink('ABC!@# 123'));
    }

    public function test_makePermalink_truncates_to_128_chars()
    {
        $input = str_repeat('a', 130);
        $output = \app\makePermalink($input);
        $this->assertEquals(128, strlen($output));
    }

    public function test_makeCode_default_length()
    {
        $code = \app\makeCode();
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code);
    }

    public function test_makeCode_custom_length()
    {
        $code = \app\makeCode(10);
        $this->assertEquals(10, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{10}$/', $code);
    }
}
