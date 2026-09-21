<?php

declare(strict_types=1);

namespace Doctrine\Common\Lexer;

use UnitEnum;

use function in_array;

/**
 * @template T of UnitEnum|string|int
 * @template-covariant V of string|int|float|bool
 */
final readonly class Token
{
    /**
     * @param V      $value    The string value of the token in the input string
     * @param T|null $type     The type of the token (identifier, numeric, string, input parameter, none)
     * @param int    $position The position of the token in the input string
     */
    public function __construct(
        public string|int|float|bool $value,
        public UnitEnum|string|int|null $type,
        public int $position,
    ) {
    }

    /** @param T ...$types */
    public function isA(...$types): bool
    {
        return in_array($this->type, $types, true);
    }
}
