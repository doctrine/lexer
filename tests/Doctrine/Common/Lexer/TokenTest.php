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
        $token = new Token('foo', 'string', 1);

        self::assertTrue($token->isA('string'));
        self::assertTrue($token->isA('int', 'string'));
        self::assertFalse($token->isA('int'));
    }

    public function testArrayAccessIsDeprecated(): void
    {
        $token = new Token('foo', 'string', 1);
        self::expectDeprecationWithIdentifier('https://github.com/doctrine/lexer/pull/75');
        self::assertSame('foo', $token['value']);
    }

    public function testCountIsDeprecated(): void
    {
        $token = new Token('foo', 'string', 1);

        self::expectDeprecationWithIdentifier('https://github.com/doctrine/lexer/pull/75');

        self::assertCount(3, $token);
    }
}
