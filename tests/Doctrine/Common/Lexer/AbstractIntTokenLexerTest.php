<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use PHPUnit\Framework\TestCase;

use function array_map;
use function count;
use function setlocale;

use const LC_ALL;

class AbstractIntTokenLexerTest extends TestCase
{
    /** @var ConcreteIntTokenLexer */
    private $concreteIntTokenLexer;

    public function setUp(): void
    {
        $this->concreteIntTokenLexer = new ConcreteIntTokenLexer();
    }

    public function tearDown(): void
    {
        setlocale(LC_ALL, null);
    }

    /**
     * @psalm-return list<array{string, list<array{value: string|int, type: int, position: int}>}>
     */
    public function dataProvider(): array
    {
        return [
            [
                'price=10',
                [
                    [
                        'value' => 'price',
                        'type' => ConcreteIntTokenLexer::T_STRING,
                        'position' => 0,
                    ],
                    [
                        'value' => '=',
                        'type' => ConcreteIntTokenLexer::T_OPERATOR,
                        'position' => 5,
                    ],
                    [
                        'value' => 10,
                        'type' => ConcreteIntTokenLexer::T_INT,
                        'position' => 6,
                    ],
                ],
            ],
        ];
    }

    public function testResetPeek(): void
    {
        $expectedTokens = [
            [
                'value' => 'price',
                'type' => ConcreteIntTokenLexer::T_STRING,
                'position' => 0,
            ],
            [
                'value' => '=',
                'type' => ConcreteIntTokenLexer::T_OPERATOR,
                'position' => 5,
            ],
            [
                'value' => 10,
                'type' => ConcreteIntTokenLexer::T_INT,
                'position' => 6,
            ],
        ];

        $this->concreteIntTokenLexer->setInput('price=10');

        $this->assertEquals($expectedTokens[0], $this->concreteIntTokenLexer->peek());
        $this->assertEquals($expectedTokens[1], $this->concreteIntTokenLexer->peek());
        $this->concreteIntTokenLexer->resetPeek();
        $this->assertEquals($expectedTokens[0], $this->concreteIntTokenLexer->peek());
    }

    public function testResetPosition(): void
    {
        $expectedTokens = [
            [
                'value' => 'price',
                'type' => ConcreteIntTokenLexer::T_STRING,
                'position' => 0,
            ],
            [
                'value' => '=',
                'type' => ConcreteIntTokenLexer::T_OPERATOR,
                'position' => 5,
            ],
            [
                'value' => 10,
                'type' => ConcreteIntTokenLexer::T_INT,
                'position' => 6,
            ],
        ];

        $this->concreteIntTokenLexer->setInput('price=10');
        $this->assertNull($this->concreteIntTokenLexer->lookahead);

        $this->assertTrue($this->concreteIntTokenLexer->moveNext());
        $this->assertEquals($expectedTokens[0], $this->concreteIntTokenLexer->lookahead);

        $this->assertTrue($this->concreteIntTokenLexer->moveNext());
        $this->assertEquals($expectedTokens[1], $this->concreteIntTokenLexer->lookahead);

        $this->concreteIntTokenLexer->resetPosition(0);

        $this->assertTrue($this->concreteIntTokenLexer->moveNext());
        $this->assertEquals($expectedTokens[0], $this->concreteIntTokenLexer->lookahead);
    }

    /**
     * @psalm-param list<array{value: string|int, type: string, position: int}>  $expectedTokens
     *
     * @dataProvider dataProvider
     */
    public function testMoveNext(string $input, array $expectedTokens): void
    {
        $this->concreteIntTokenLexer->setInput($input);
        $this->assertNull($this->concreteIntTokenLexer->lookahead);

        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteIntTokenLexer->moveNext());
            $this->assertEquals($expectedTokens[$i], $this->concreteIntTokenLexer->lookahead);
        }

        $this->assertFalse($this->concreteIntTokenLexer->moveNext());
        $this->assertNull($this->concreteIntTokenLexer->lookahead);
    }

    public function testSkipUntil(): void
    {
        $this->concreteIntTokenLexer->setInput('price=10');

        $this->assertTrue($this->concreteIntTokenLexer->moveNext());
        $this->concreteIntTokenLexer->skipUntil(ConcreteIntTokenLexer::T_OPERATOR);

        $this->assertEquals(
            [
                'value' => '=',
                'type' => ConcreteIntTokenLexer::T_OPERATOR,
                'position' => 5,
            ],
            $this->concreteIntTokenLexer->lookahead
        );
    }

    public function testUtf8Mismatch(): void
    {
        $this->concreteIntTokenLexer->setInput("\xE9=10");

        $this->assertTrue($this->concreteIntTokenLexer->moveNext());

        $this->assertEquals(
            [
                'value' => "\xE9=10",
                'type' => ConcreteIntTokenLexer::T_STRING,
                'position' => 0,
            ],
            $this->concreteIntTokenLexer->lookahead
        );
    }

    /**
     * @psalm-param list<array{value: string|int, type: string, position: int}> $expectedTokens
     *
     * @dataProvider dataProvider
     */
    public function testPeek(string $input, array $expectedTokens): void
    {
        $this->concreteIntTokenLexer->setInput($input);
        foreach ($expectedTokens as $expectedToken) {
            $this->assertEquals($expectedToken, $this->concreteIntTokenLexer->peek());
        }

        $this->assertNull($this->concreteIntTokenLexer->peek());
    }

    /**
     * @psalm-param list<array{value: string|int, type: string, position: int}> $expectedTokens
     *
     * @dataProvider dataProvider
     */
    public function testGlimpse(string $input, array $expectedTokens): void
    {
        $this->concreteIntTokenLexer->setInput($input);

        foreach ($expectedTokens as $expectedToken) {
            $this->assertEquals($expectedToken, $this->concreteIntTokenLexer->glimpse());
            $this->concreteIntTokenLexer->moveNext();
        }

        $this->assertNull($this->concreteIntTokenLexer->peek());
    }

    /**
     * @psalm-return list<array{string, int, string}>
     */
    public function inputUntilPositionDataProvider(): array
    {
        return [
            ['price=10', 5, 'price'],
        ];
    }

    /**
     * @dataProvider inputUntilPositionDataProvider
     */
    public function testGetInputUntilPosition(
        string $input,
        int $position,
        string $expectedInput
    ): void {
        $this->concreteIntTokenLexer->setInput($input);

        $this->assertSame($expectedInput, $this->concreteIntTokenLexer->getInputUntilPosition($position));
    }

    /**
     * @psalm-param list<array{value: string|int, type: string, position: int}> $expectedTokens
     *
     * @dataProvider dataProvider
     */
    public function testIsNextToken(string $input, array $expectedTokens): void
    {
        $this->concreteIntTokenLexer->setInput($input);

        $this->concreteIntTokenLexer->moveNext();
        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteIntTokenLexer->isNextToken($expectedTokens[$i]['type']));
            $this->concreteIntTokenLexer->moveNext();
        }
    }

    /**
     * @psalm-param list<array{value: string|int, type: string, position: int}> $expectedTokens
     *
     * @dataProvider dataProvider
     */
    public function testIsNextTokenAny(string $input, array $expectedTokens): void
    {
        $allTokenTypes = array_map(static function ($token) {
            return $token['type'];
        }, $expectedTokens);

        $this->concreteIntTokenLexer->setInput($input);

        $this->concreteIntTokenLexer->moveNext();
        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteIntTokenLexer->isNextTokenAny([$expectedTokens[$i]['type']]));
            $this->assertTrue($this->concreteIntTokenLexer->isNextTokenAny($allTokenTypes));
            $this->concreteIntTokenLexer->moveNext();
        }
    }

    public function testGetLiteral(): void
    {
        $this->assertSame('Doctrine\Tests\Common\Lexer\ConcreteIntTokenLexer::T_INT', $this->concreteIntTokenLexer->getLiteral(ConcreteIntTokenLexer::T_INT));
        $this->assertSame('fake_token', $this->concreteIntTokenLexer->getLiteral('fake_token'));
    }

    public function testIsA(): void
    {
        $this->assertTrue($this->concreteIntTokenLexer->isA(11, ConcreteIntTokenLexer::T_INT));
        $this->assertTrue($this->concreteIntTokenLexer->isA(1.1, ConcreteIntTokenLexer::T_INT));
        $this->assertTrue($this->concreteIntTokenLexer->isA('=', ConcreteIntTokenLexer::T_OPERATOR));
        $this->assertTrue($this->concreteIntTokenLexer->isA('>', ConcreteIntTokenLexer::T_OPERATOR));
        $this->assertTrue($this->concreteIntTokenLexer->isA('<', ConcreteIntTokenLexer::T_OPERATOR));
        $this->assertTrue($this->concreteIntTokenLexer->isA('fake_text', ConcreteIntTokenLexer::T_STRING));
    }
}
