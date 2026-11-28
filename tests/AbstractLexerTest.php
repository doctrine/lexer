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
use function explode;
use function fclose;
use function feof;
use function fread;
use function function_exists;
use function hrtime;
use function is_int;
use function is_numeric;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function setlocale;
use function str_repeat;
use function stream_select;
use function stream_set_blocking;
use function usleep;

use const LC_ALL;
use const PHP_BINARY;

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

    /** @phpstan-return list<array{string, list<Token<string, string|int>>}> */
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

    /**
     * @see https://github.com/doctrine/lexer/issues/53
     *
     * resetPosition() takes a character offset, not a token index. The input
     * below has multi-character tokens, so the second token's offset (6)
     * differs from its index (1).
     */
    public function testResetPositionByTokenPosition(): void
    {
        $this->concreteLexer->setInput('price=10');

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertTrue($this->concreteLexer->moveNext());

        $target = $this->concreteLexer->lookahead;
        $this->assertSame('=', $target->value);
        $this->assertSame(5, $target->position);

        // Move the cursor past the index that used to coincide with the target.
        $remainingTokens = 0;
        while ($this->concreteLexer->moveNext()) {
            $remainingTokens++;
        }

        $this->assertSame(1, $remainingTokens);
        $this->assertNull($this->concreteLexer->lookahead);

        $this->concreteLexer->resetPosition($target->position);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($target, $this->concreteLexer->lookahead);
    }

    public function testResetPositionPastTheLastToken(): void
    {
        $this->concreteLexer->setInput('price=10');

        $tokenCount = 0;
        while ($this->concreteLexer->moveNext()) {
            $tokenCount++;
        }

        $this->assertSame(3, $tokenCount);

        // No token starts at or after this offset, so the lexer lands at the end.
        $this->concreteLexer->resetPosition(99);

        $this->assertFalse($this->concreteLexer->moveNext());
        $this->assertNull($this->concreteLexer->lookahead);
    }

    /** @phpstan-param list<Token<string, string|int>>  $expectedTokens */
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

    /** @phpstan-param list<Token<string, string|int>> $expectedTokens */
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

    /** @phpstan-param list<Token<string, string|int>> $expectedTokens */
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

    /** @phpstan-return list<array{string, int, string}> */
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

    /** @phpstan-param list<Token<string, string|int>> $expectedTokens */
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

    /** @phpstan-param list<Token<string, string|int>> $expectedTokens */
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

    public function testScanLargeInputPreservesTokens(): void
    {
        $repeats = 8000;
        $this->concreteLexer->setInput(str_repeat('price=10 ', $repeats));

        $count = 0;
        $first = null;
        $last  = null;
        while ($this->concreteLexer->moveNext()) {
            if ($first === null) {
                $first = $this->concreteLexer->lookahead;
            }

            $last = $this->concreteLexer->lookahead;
            $count++;
        }

        self::assertSame($repeats * 3, $count);
        self::assertEquals(new Token('price', 'string', 0), $first);
        self::assertEquals(new Token(10, 'int', ($repeats - 1) * 9 + 6), $last);

        // Unmatched spans plus named/nested/optional capturing groups above threshold
        $complexLexer = new MutableLexer();
        $complexLexer->addCatchablePattern('(?P<pair>(foo)?(bar))');
        $prefix = str_repeat('z', 70000);
        $complexLexer->setInput($prefix . ' foobar bar tail');

        $collected = [];
        while ($complexLexer->moveNext()) {
            $collected[] = [$complexLexer->lookahead->value, $complexLexer->lookahead->position];
        }

        self::assertSame(
            [
                [$prefix, 0],
                ['foobar', 70001],
                ['foobar', 70001],
                ['foo', 70001],
                ['bar', 70004],
                ['bar', 70008],
                ['bar', 70008],
                ['bar', 70008],
                ['tail', 70012],
            ],
            $collected,
        );

        // Invalid UTF-8 above threshold preserves the PHP bug #78122 single-token fallback
        $badUtf8 = str_repeat('a=', 35000) . "\xFF";
        $this->concreteLexer->setInput($badUtf8);
        self::assertTrue($this->concreteLexer->moveNext());
        self::assertEquals(new Token($badUtf8, 'string', 0), $this->concreteLexer->lookahead);
        self::assertFalse($this->concreteLexer->moveNext());

        // Zero-width pattern above threshold falls back cleanly to preg_split() semantics
        $zeroWidthLexer = new /** @template-extends AbstractLexer<int, string> */ class () extends AbstractLexer {
            /** {@inheritDoc} */
            protected function getCatchablePatterns(): array
            {
                return ['[a-z]+'];
            }

            /** {@inheritDoc} */
            protected function getNonCatchablePatterns(): array
            {
                return [];
            }

            protected function getType(string &$value): int
            {
                return 1;
            }
        };

        $zwInput = str_repeat('a', 70000) . '!é';
        $zeroWidthLexer->setInput($zwInput);
        $zwTokens = [];
        while ($zeroWidthLexer->moveNext()) {
            $zwTokens[] = [$zeroWidthLexer->lookahead->value, $zeroWidthLexer->lookahead->position];
        }

        self::assertSame(
            [
                [str_repeat('a', 70000), 0],
                ['!', 70000],
                ['é', 70001],
            ],
            $zwTokens,
        );
    }

    public function testScanFreesMatchBufferWhileBuildingTokens(): void
    {
        if (
            ! function_exists('proc_open')
            || ! function_exists('proc_close')
            || ! function_exists('proc_get_status')
            || ! function_exists('proc_terminate')
        ) {
            self::markTestSkipped('proc_open(), proc_close(), proc_get_status(), and proc_terminate() are required to measure isolated scan peak memory.');
        }

        $autoload = __DIR__ . '/../vendor/autoload.php';
        $script   = <<<'PHP'
require $argv[1];
$lexer = new Doctrine\Tests\Common\Lexer\ConcreteLexer();
$base = memory_get_usage(false);
$lexer->setInput(str_repeat('(', 150000));
$retained = memory_get_usage(false) - $base;
$peak = memory_get_peak_usage(false) - $base;
$count = 0;
while ($lexer->moveNext()) {
    $count++;
}
echo $count . ':' . $retained . ':' . $peak;
PHP;

        $process = proc_open(
            [PHP_BINARY, '-d', 'memory_limit=512M', '-r', $script, $autoload],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        self::assertIsResource($process);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout   = '';
        $stderr   = '';
        $open     = [1 => $pipes[1], 2 => $pipes[2]];
        $deadline = hrtime(true) + 30_000_000_000;
        $timedOut = false;

        while ($open !== []) {
            $remainingNs = $deadline - hrtime(true);
            if ($remainingNs <= 0) {
                $timedOut = true;
                proc_terminate($process);
                break;
            }

            $read   = $open;
            $write  = null;
            $except = null;
            $sec    = (int) ($remainingNs / 1_000_000_000);
            $usec   = (int) (($remainingNs % 1_000_000_000) / 1_000);

            $ready = stream_select($read, $write, $except, $sec, $usec);
            if ($ready === false || $ready === 0) {
                continue;
            }

            foreach ($read as $fd => $stream) {
                $chunk = fread($stream, 8192);
                if ($chunk !== false && $chunk !== '') {
                    if ($fd === 1) {
                        $stdout .= $chunk;
                    } else {
                        $stderr .= $chunk;
                    }
                }

                if ($chunk !== false && ! feof($stream)) {
                    continue;
                }

                fclose($stream);
                unset($open[$fd]);
            }
        }

        foreach ($open as $stream) {
            fclose($stream);
        }

        $status = proc_get_status($process);
        while (! $timedOut && $status['running']) {
            if (hrtime(true) >= $deadline) {
                $timedOut = true;
                proc_terminate($process);
                break;
            }

            usleep(10_000);
            $status = proc_get_status($process);
        }

        $exitCode = proc_close($process);
        if ($status['exitcode'] !== -1) {
            $exitCode = $status['exitcode'];
        }

        self::assertFalse($timedOut, 'Subprocess timed out while measuring scan peak memory.');
        self::assertSame(0, $exitCode, $stderr);

        [$count, $retainedBytes, $peakBytes] = array_map(static fn (string $part): int => (int) $part, explode(':', $stdout));
        self::assertSame(150000, $count);
        self::assertGreaterThan(0, $retainedBytes);
        self::assertLessThan(2.5, $peakBytes / $retainedBytes);
    }
}
