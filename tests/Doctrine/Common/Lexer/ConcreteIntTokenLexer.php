<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

use function in_array;
use function is_numeric;
use function is_string;

class ConcreteIntTokenLexer extends AbstractLexer
{
    public const T_INT      = 1;
    public const T_STRING   = 2;
    public const T_OPERATOR = 3;

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

            return self::T_INT;
        }

        if (in_array($value, ['=', '<', '>'])) {
            return self::T_OPERATOR;
        }

        if (is_string($value)) {
            return self::T_STRING;
        }
    }
}
