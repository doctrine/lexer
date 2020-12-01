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

    /**
     * {@inheritDoc}
     */
    protected function getCatchablePatterns()
    {
        return [
            '=|<|>',
            '[a-z]+',
            '\d+',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns()
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    /**
     * {@inheritDoc}
     */
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
    }
}
