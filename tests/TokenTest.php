<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Token;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    use VerifyDeprecations;

    public function testIsA(): void
    {
        /** @var Token<'string'|'int', string> $token */
        $token = new Token('foo', 'string', 1);

        self::assertTrue($token->isA('string'));
        self::assertTrue($token->isA('int', 'string'));
        self::assertFalse($token->isA('int'));
    }

    public function testOffsetGetIsDeprecated(): void
    {
        $token = new Token('foo', 'string', 1);
        self::expectDeprecationWithIdentifier('https://github.com/doctrine/lexer/pull/79');
        self::assertSame('foo', $token['value']);
    }

    public function testOffsetExistsIsDeprecated(): void
    {
        $token = new Token('foo', 'string', 1);
        self::expectDeprecationWithIdentifier('https://github.com/doctrine/lexer/pull/79');
        self::assertTrue(isset($token['value']));
    }

    public function testOffsetSetIsDeprecated(): void
    {
        $token = new Token('foo', 'string', 1);
        self::expectDeprecationWithIdentifier('https://github.com/doctrine/lexer/pull/79');
        $token['value'] = 'bar';
        self::assertSame('bar', $token->value);
    }

    public function testOffsetUnsetIsDeprecated(): void
    {
        $token = new Token('foo', 'string', 1);
        self::expectDeprecationWithIdentifier('https://github.com/doctrine/lexer/pull/79');
        unset($token['value']);
        self::assertNull($token->value);
    }
}
