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
}
