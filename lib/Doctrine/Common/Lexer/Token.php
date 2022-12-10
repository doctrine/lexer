<?php

declare(strict_types=1);

namespace Doctrine\Common\Lexer;

use ArrayAccess;
use Doctrine\Deprecations\Deprecation;
use ReturnTypeWillChange;
use UnitEnum;

use function in_array;

/**
 * @template T of UnitEnum|string|int
 * @implements ArrayAccess<string,mixed>
 */
final class Token implements ArrayAccess
{
    /**
     * The string value of the token in the input string
     *
     * @readonly
     * @var string|int
     */
    public $value;

    /**
     * The type of the token (identifier, numeric, string, input parameter, none)
     *
     * @readonly
     * @var T|null
     */
    public $type;

    /**
     * The position of the token in the input string
     *
     * @readonly
     * @var int
     */
    public $position;

    /**
     * @param string|int $value
     * @param T|null     $type
     */
    public function __construct($value, $type, int $position)
    {
        $this->value    = $value;
        $this->type     = $type;
        $this->position = $position;
    }

    /** @param T ...$types */
    public function isA(...$types): bool
    {
        return in_array($this->type, $types, true);
    }

    /**
     * @deprecated Use the value, type or position property instead
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        Deprecation::trigger(
            'doctrine/lexer',
            'https://github.com/doctrine/lexer/pull/79',
            'Accessing %s properties via ArrayAccess is deprecated, use the value, type or position property instead',
            self::class
        );

        return in_array($offset, ['value', 'type', 'position'], true);
    }

    /**
     * @deprecated Use the value, type or position property instead
     * {@inheritDoc}
     *
     * @param array-key $offset
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        Deprecation::trigger(
            'doctrine/lexer',
            'https://github.com/doctrine/lexer/pull/79',
            'Accessing %s properties via ArrayAccess is deprecated, use the value, type or position property instead',
            self::class
        );

        return $this->$offset;
    }

    /**
     * @deprecated no replacement planned
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        Deprecation::trigger(
            'doctrine/lexer',
            'https://github.com/doctrine/lexer/pull/79',
            'Setting %s properties via ArrayAccess is deprecated',
            self::class
        );

        $this->$offset = $value;
    }

    /**
     * @deprecated no replacement planned
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        Deprecation::trigger(
            'doctrine/lexer',
            'https://github.com/doctrine/lexer/pull/79',
            'Setting %s properties via ArrayAccess is deprecated',
            self::class
        );

        $this->$offset = null;
    }
}
