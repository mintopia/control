<?php

namespace Tests\Unit\app;

use PHPUnit\Framework\TestCase;

use function app\makeCode;
use function app\makePermalink;

class HelpersTest extends TestCase
{
    public function testMakePermalinkBasic()
    {
        $this->assertEquals('hello-world', makePermalink('Hello World'));
    }

    public function testMakePermalinkRemovesSpecialCharacters()
    {
        $this->assertEquals('abc-123', makePermalink('ABC!@# 123'));
    }

    public function testMakePermalinkTruncatesTo128Chars()
    {
        $input = str_repeat('a', 130);
        $output = makePermalink($input);
        $this->assertEquals(128, strlen($output));
    }

    public function testMakeCodeDefaultLength()
    {
        $code = makeCode();
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code);
    }

    public function testMakeCodeCustomLength()
    {
        $code = makeCode(10);
        $this->assertEquals(10, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{10}$/', $code);
    }
}
