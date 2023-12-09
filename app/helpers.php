<?php
namespace App;
function makePermalink(string $input): string
{
    $input = strtolower($input);
    $patterns = array("/ /", "/[^a-z0-9-]/i");
    $replacements = array("-", "");
    $output = preg_replace($patterns, $replacements, $input);
    return substr($output, 0, 128);
};

function makeCode(int $length = 6): string
{
    $code = '';
    $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < $length; $i++) {
        $code .= substr($charset, mt_rand(0, strlen($charset) - 1), 1);
    }
    return $code;
}
