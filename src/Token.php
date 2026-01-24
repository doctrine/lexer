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
     * @param V      $value
     * @param T|null $type
     */
    public function __construct(
        /**
         * The string value of the token in the input string
         *
         * @var V
         */
        public string|int|float|bool $value,
        /**
         * The type of the token (identifier, numeric, string, input parameter, none)
         *
         * @var T|null
         */
        public UnitEnum|string|int|null $type,
        /** The position of the token in the input string */
        public int $position,
    ) {
    }

    /** @param T ...$types */
    public function isA(...$types): bool
    {
        return in_array($this->type, $types, true);
    }
}
