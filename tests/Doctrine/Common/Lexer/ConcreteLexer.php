<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;
use function in_array;
use function is_numeric;
use function is_string;

class ConcreteLexer extends AbstractLexer
{
    public const INT = 'int';

    protected function getCatchablePatterns()
    {
        return [
            '=|<|>',
            '[a-z]+',
            '\d+',
        ];
    }

    protected function getNonCatchablePatterns()
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    protected function getType(&$value)
    {
        if (is_numeric($value)) {
            $value = (int) $value;

            return 'int';
        }
        if (in_array($value, ['=', '<', '>'])) {
            return 'operator';
        }
        if (is_string($value)) {
            return 'string';
        }

        return null;
    }
}
