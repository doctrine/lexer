<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;
use Doctrine\Common\Lexer\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;
use function assert;
use function count;
use function is_int;
use function is_numeric;
use function setlocale;

use const LC_ALL;

class AbstractLexerTest extends TestCase
{
    private ConcreteLexer $concreteLexer;

    public function setUp(): void
    {
        $this->concreteLexer = new ConcreteLexer();
    }

    public function tearDown(): void
    {
        setlocale(LC_ALL, null);
    }

    /** @psalm-return list<array{string, list<Token<string, string|int>>}> */
    public static function dataProvider(): array
    {
        return [
            [
                'price=10',
                [
                    new Token('price', 'string', 0),
                    new Token('=', 'operator', 5),
                    new Token(10, 'int', 6),
                ],
            ],
        ];
    }

    public function testResetPeek(): void
    {
        $expectedTokens = [
            new Token('price', 'string', 0),
            new Token('=', 'operator', 5),
            new Token(10, 'int', 6),
        ];

        $this->concreteLexer->setInput('price=10');

        $this->assertEquals($expectedTokens[0], $this->concreteLexer->peek());
        $this->assertEquals($expectedTokens[1], $this->concreteLexer->peek());
        $this->concreteLexer->resetPeek();
        $this->assertEquals($expectedTokens[0], $this->concreteLexer->peek());
    }

    public function testResetPosition(): void
    {
        $expectedTokens = [
            new Token('price', 'string', 0),
            new Token('=', 'operator', 5),
            new Token(10, 'int', 6),
        ];

        $this->concreteLexer->setInput('price=10');
        $this->assertNull($this->concreteLexer->lookahead);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($expectedTokens[0], $this->concreteLexer->lookahead);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($expectedTokens[1], $this->concreteLexer->lookahead);

        $this->concreteLexer->resetPosition(0);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($expectedTokens[0], $this->concreteLexer->lookahead);
    }

    /** @psalm-param list<Token<string, string|int>>  $expectedTokens */
    #[DataProvider('dataProvider')]
    public function testMoveNext(string $input, array $expectedTokens): void
    {
        $this->concreteLexer->setInput($input);
        $this->assertNull($this->concreteLexer->lookahead);

        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteLexer->moveNext());
            $this->assertEquals($expectedTokens[$i], $this->concreteLexer->lookahead);
        }

        $this->assertFalse($this->concreteLexer->moveNext());
        $this->assertNull($this->concreteLexer->lookahead);
    }

    public function testSkipUntil(): void
    {
        $this->concreteLexer->setInput('price=10');

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->concreteLexer->skipUntil('operator');

        $this->assertEquals(
            new Token('=', 'operator', 5),
            $this->concreteLexer->lookahead,
        );
    }

    public function testUtf8Mismatch(): void
    {
        $this->concreteLexer->setInput("\xE9=10");

        $this->assertTrue($this->concreteLexer->moveNext());

        $this->assertEquals(
            new Token("\xE9=10", 'string', 0),
            $this->concreteLexer->lookahead,
        );
    }

    /** @psalm-param list<Token<string, string|int>> $expectedTokens */
    #[DataProvider('dataProvider')]
    public function testPeek(string $input, array $expectedTokens): void
    {
        $this->concreteLexer->setInput($input);
        foreach ($expectedTokens as $expectedToken) {
            $actualToken = $this->concreteLexer->peek();
            assert($actualToken !== null);
            $this->assertEquals($expectedToken, $actualToken);
            $this->assertSame($expectedToken->value, $actualToken->value);
            $this->assertSame($expectedToken->type, $actualToken->type);
            $this->assertSame($expectedToken->position, $actualToken->position);
        }

        $this->assertNull($this->concreteLexer->peek());
    }

    /** @psalm-param list<Token<string, string|int>> $expectedTokens */
    #[DataProvider('dataProvider')]
    public function testGlimpse(string $input, array $expectedTokens): void
    {
        $this->concreteLexer->setInput($input);

        foreach ($expectedTokens as $expectedToken) {
            $actualToken = $this->concreteLexer->glimpse();
            assert($actualToken !== null);
            $this->assertEquals($expectedToken, $actualToken);
            $this->assertEquals($expectedToken, $this->concreteLexer->glimpse());
            $this->assertSame($expectedToken->value, $actualToken->value);
            $this->assertSame($expectedToken->type, $actualToken->type);
            $this->assertSame($expectedToken->position, $actualToken->position);
            $this->concreteLexer->moveNext();
        }

        $this->assertNull($this->concreteLexer->peek());
    }

    /** @psalm-return list<array{string, int, string}> */
    public static function inputUntilPositionDataProvider(): array
    {
        return [
            ['price=10', 5, 'price'],
        ];
    }

    #[DataProvider('inputUntilPositionDataProvider')]
    public function testGetInputUntilPosition(
        string $input,
        int $position,
        string $expectedInput,
    ): void {
        $this->concreteLexer->setInput($input);

        $this->assertSame($expectedInput, $this->concreteLexer->getInputUntilPosition($position));
    }

    /** @psalm-param list<Token<string, string|int>> $expectedTokens */
    #[DataProvider('dataProvider')]
    public function testIsNextToken(string $input, array $expectedTokens): void
    {
        $this->concreteLexer->setInput($input);

        $this->concreteLexer->moveNext();
        for ($i = 0; $i < count($expectedTokens); $i++) {
            assert($expectedTokens[$i]->type !== null);
            $this->assertTrue($this->concreteLexer->isNextToken($expectedTokens[$i]->type));
            $this->concreteLexer->moveNext();
        }
    }

    /** @psalm-param list<Token<string, string|int>> $expectedTokens */
    #[DataProvider('dataProvider')]
    public function testIsNextTokenAny(string $input, array $expectedTokens): void
    {
        $allTokenTypes = array_map(static function ($token): string {
            assert($token->type !== null);

            return $token->type;
        }, $expectedTokens);

        $this->concreteLexer->setInput($input);

        $this->concreteLexer->moveNext();
        for ($i = 0; $i < count($expectedTokens); $i++) {
            assert($expectedTokens[$i]->type !== null);
            $this->assertTrue($this->concreteLexer->isNextTokenAny([$expectedTokens[$i]->type]));
            $this->assertTrue($this->concreteLexer->isNextTokenAny($allTokenTypes));
            $this->concreteLexer->moveNext();
        }
    }

    public function testGetLiteral(): void
    {
        $this->assertSame('Doctrine\Tests\Common\Lexer\ConcreteLexer::INT', $this->concreteLexer->getLiteral('int'));
        $this->assertSame('fake_token', $this->concreteLexer->getLiteral('fake_token'));
    }

    public function testGetLiteralWithEnumLexer(): void
    {
        $enumLexer = new EnumLexer();
        $this->assertSame(
            'Doctrine\Tests\Common\Lexer\TokenType::OPERATOR',
            $enumLexer->getLiteral(TokenType::OPERATOR),
        );
    }

    public function testIsA(): void
    {
        $this->assertTrue($this->concreteLexer->isA('11', 'int'));
        $this->assertTrue($this->concreteLexer->isA('1.1', 'int'));
        $this->assertTrue($this->concreteLexer->isA('=', 'operator'));
        $this->assertTrue($this->concreteLexer->isA('>', 'operator'));
        $this->assertTrue($this->concreteLexer->isA('<', 'operator'));
        $this->assertTrue($this->concreteLexer->isA('fake_text', 'string'));
    }

    public function testAddCatchablePatternsToMutableLexer(): void
    {
        $mutableLexer = new MutableLexer();
        $mutableLexer->addCatchablePattern('[a-z]');
        $mutableLexer->setInput('one');
        $token = $mutableLexer->glimpse();

        $this->assertNotNull($token);
        $this->assertEquals('o', $token->value);

        $mutableLexer = new MutableLexer();
        $mutableLexer->addCatchablePattern('[a-z]+');
        $mutableLexer->setInput('one');
        $token = $mutableLexer->glimpse();

        $this->assertNotNull($token);
        $this->assertEquals('one', $token->value);
    }

    public function testMarkerAnnotationLocaleTr(): void
    {
        setlocale(LC_ALL, 'tr_TR.utf8', 'tr_TR');
        $mutableLexer = new MutableLexer();
        $mutableLexer->addCatchablePattern('[a-z_\\\][a-z0-9_\:\\\]*[a-z_][a-z0-9_]*');
        $mutableLexer->addCatchablePattern('(?:[+-]?[0-9]+(?:[\.][0-9]+)*)(?:[eE][+-]?[0-9]+)?');
        $mutableLexer->addCatchablePattern('"(?:""|[^"])*+"');
        $mutableLexer->setInput('@ODM\Id');

        self::assertNull($mutableLexer->token);
        self::assertNull($mutableLexer->lookahead);
        self::assertTrue($mutableLexer->moveNext());
        self::assertNull($mutableLexer->token);
        self::assertNotNull($mutableLexer->lookahead);
        self::assertEquals('@', $mutableLexer->lookahead->value);
        self::assertTrue($mutableLexer->moveNext());
        self::assertNotNull($mutableLexer->token);
        self::assertEquals('@', $mutableLexer->token->value);
        self::assertEquals('ODM\Id', $mutableLexer->lookahead->value);
    }

    public function testCanTokenizeFloatValue(): void
    {
        $lexer = new /** @template-extends AbstractLexer<int, string|int|float|bool> */ class () extends AbstractLexer {
            final public const T_NONE    = 1;
            final public const T_INTEGER = 2;
            final public const T_FLOAT   = 4;
            final public const T_BOOL    = 8;

            protected function getType(string|int|float|bool &$value): int
            {
                if ($value === 'y') {
                    $value = true;

                    return self::T_BOOL;
                }

                if (is_numeric($value)) {
                    $value += 0;

                    if (is_int($value)) {
                        return self::T_INTEGER;
                    }

                    return self::T_FLOAT;
                }

                return self::T_NONE;
            }

            /** {@inheritDoc} */
            protected function getCatchablePatterns(): array
            {
                return [
                    '(?:[0-9]+)(?:[\.][0-9]+)?(?:e[+-]?[0-9]+)?',
                    'y',
                ];
            }

            /** {@inheritDoc} */
            protected function getNonCatchablePatterns(): array
            {
                return ['\s+'];
            }
        };

        $lexer->setInput('123.456');
        $token = $lexer->peek();
        assert($token !== null);
        self::assertSame(123.456, $token->value, 'expect a real float, not a numerical string');

        $lexer->setInput('y');
        $token = $lexer->peek();
        assert($token !== null);
        self::assertTrue($token->value, 'expect a real bool, not a numerical string');
    }
}
