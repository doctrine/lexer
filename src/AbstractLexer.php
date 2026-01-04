<?php

declare(strict_types=1);

namespace Doctrine\Common\Lexer;

use ReflectionClass;
use UnitEnum;

use function implode;
use function preg_split;
use function sprintf;
use function substr;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;
use const PREG_SPLIT_OFFSET_CAPTURE;

/**
 * Base class for writing simple lexers, i.e. for creating small DSLs.
 *
 * @template T of UnitEnum|string|int
 * @template V of string|int|float|bool
 */
abstract class AbstractLexer
{
    /**
     * Lexer original input string.
     */
    private string $input;

    /**
     * Array of scanned tokens.
     *
     * @var list<Token<T, V>>
     */
    private array $tokens = [];

    /**
     * Current lexer position in input string.
     */
    private int $position = 0;

    /**
     * Current peek of current lexer position.
     */
    private int $peek = 0;

    /**
     * The next token in the input.
     *
     * @var Token<T, V>|null
     */
    public Token|null $lookahead;

    /**
     * The last matched/seen token.
     *
     * @var Token<T, V>|null
     */
    public Token|null $token;

    /**
     * Composed regex for input parsing.
     *
     * @var non-empty-string|null
     */
    private string|null $regex = null;

    /**
     * Sets the input data to be tokenized.
     *
     * The Lexer is immediately reset and the new input tokenized.
     * Any unprocessed tokens from any previous input are lost.
     *
     * @param string $input The input to be tokenized.
     */
    public function setInput(string $input): void
    {
        $this->input  = $input;
        $this->tokens = [];

        $this->reset();
        $this->scan($input);
    }

    /**
     * Resets the lexer.
     */
    public function reset(): void
    {
        $this->lookahead = null;
        $this->token     = null;
        $this->peek      = 0;
        $this->position  = 0;
    }

    /**
     * Resets the peek pointer to 0.
     */
    public function resetPeek(): void
    {
        $this->peek = 0;
    }

    /**
     * Resets the lexer position on the input to the given position.
     *
     * @param int $position Position to place the lexical scanner.
     */
    public function resetPosition(int $position = 0): void
    {
        $this->position = $position;
    }

    /**
     * Retrieve the original lexer's input until a given position.
     */
    public function getInputUntilPosition(int $position): string
    {
        return substr($this->input, 0, $position);
    }

    /**
     * Checks whether a given token matches the current lookahead.
     *
     * @param T $type
     *
     * @phpstan-assert-if-true !=null $this->lookahead
     */
    public function isNextToken(int|string|UnitEnum $type): bool
    {
        return $this->lookahead !== null && $this->lookahead->isA($type);
    }

    /**
     * Checks whether any of the given tokens matches the current lookahead.
     *
     * @param list<T> $types
     *
     * @phpstan-assert-if-true !=null $this->lookahead
     */
    public function isNextTokenAny(array $types): bool
    {
        return $this->lookahead !== null && $this->lookahead->isA(...$types);
    }

    /**
     * Moves to the next token in the input string.
     *
     * @phpstan-impure
     * @phpstan-assert-if-true !null $this->lookahead
     */
    public function moveNext(): bool
    {
        $this->peek      = 0;
        $this->token     = $this->lookahead;
        $this->lookahead = isset($this->tokens[$this->position])
            ? $this->tokens[$this->position++] : null;

        return $this->lookahead !== null;
    }

    /**
     * Tells the lexer to skip input tokens until it sees a token with the given value.
     *
     * @param T $type The token type to skip until.
     */
    public function skipUntil(int|string|UnitEnum $type): void
    {
        while ($this->lookahead !== null && ! $this->lookahead->isA($type)) {
            $this->moveNext();
        }
    }

    /**
     * Checks if given value is identical to the given token.
     */
    public function isA(string $value, int|string|UnitEnum $token): bool
    {
        return $this->getType($value) === $token;
    }

    /**
     * Moves the lookahead token forward.
     *
     * @return Token<T, V>|null The next token or NULL if there are no more tokens ahead.
     */
    public function peek(): Token|null
    {
        if (isset($this->tokens[$this->position + $this->peek])) {
            return $this->tokens[$this->position + $this->peek++];
        }

        return null;
    }

    /**
     * Peeks at the next token, returns it and immediately resets the peek.
     *
     * @return Token<T, V>|null The next token or NULL if there are no more tokens ahead.
     *
     * @phpstan-impure
     */
    public function glimpse(): Token|null
    {
        $peek       = $this->peek();
        $this->peek = 0;

        return $peek;
    }

    /**
     * Scans the input string for tokens.
     *
     * @param string $input A query string.
     */
    protected function scan(string $input): void
    {
        if (! isset($this->regex)) {
            $this->regex = sprintf(
                '/(%s)|%s/%s',
                implode(')|(', $this->getCatchablePatterns()),
                implode('|', $this->getNonCatchablePatterns()),
                $this->getModifiers(),
            );
        }

        $flags   = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $matches = preg_split($this->regex, $input, -1, $flags);

        if ($matches === false) {
            // Work around https://bugs.php.net/78122
            $matches = [[$input, 0]];
        }

        foreach ($matches as $match) {
            // Must remain before 'value' assignment since it can change content
            $firstMatch = $match[0];
            $type       = $this->getType($firstMatch);

            $this->tokens[] = new Token(
                $firstMatch,
                $type,
                $match[1],
            );
        }
    }

    /**
     * Gets the literal for a given token.
     *
     * @param T $token
     */
    public function getLiteral(int|string|UnitEnum $token): int|string
    {
        if ($token instanceof UnitEnum) {
            return $token::class . '::' . $token->name;
        }

        $className = static::class;

        $reflClass = new ReflectionClass($className);
        $constants = $reflClass->getConstants();

        foreach ($constants as $name => $value) {
            if ($value === $token) {
                return $className . '::' . $name;
            }
        }

        return $token;
    }

    /**
     * Regex modifiers
     */
    protected function getModifiers(): string
    {
        return 'iu';
    }

    /**
     * Lexical catchable patterns.
     *
     * @return string[]
     */
    abstract protected function getCatchablePatterns(): array;

    /**
     * Lexical non-catchable patterns.
     *
     * @return string[]
     */
    abstract protected function getNonCatchablePatterns(): array;

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @return T|null
     *
     * @param-out V $value
     */
    abstract protected function getType(string &$value);
}
