<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;
use Override;

use function in_array;
use function is_numeric;

/** @extends AbstractLexer<TokenType, string|int> */
class EnumLexer extends AbstractLexer
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function getCatchablePatterns(): array
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
    #[Override]
    protected function getNonCatchablePatterns(): array
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    #[Override]
    protected function getType(string &$value): TokenType
    {
        if (is_numeric($value)) {
            $value = (int) $value;

            return TokenType::INT;
        }

        if (in_array($value, ['=', '<', '>'])) {
            return TokenType::OPERATOR;
        }

        return TokenType::STRING;
    }
}
