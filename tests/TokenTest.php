<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\Token;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    public function testIsA(): void
    {
        /** @var Token<'string'|'int', string> $token */
        $token = new Token('foo', 'string', 1);

        self::assertTrue($token->isA('string'));
        self::assertTrue($token->isA('int', 'string'));
        self::assertFalse($token->isA('int'));
    }
}
