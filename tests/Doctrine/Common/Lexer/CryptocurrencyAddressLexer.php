<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

use function array_values;
use function count;

class CryptocurrencyAddressLexer extends AbstractLexer
{
    /** @var string[] */
    private $catchablePatterns = [
        'bitcoin' => '^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}$',
        'litecoin' => '^[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}$',
        'ethereum' => '^0x[a-fA-F0-9]{40}$',
    ];

    /**
     * {@inheritDoc}
     */
    protected function getCatchablePatterns()
    {
        return $this->catchablePatterns;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null
     */
    protected function getType(&$value)
    {
        $items = $this->caughtByPatterns($value);

        return array_values($items)[0] ?? null;
    }

    /**
     * @param string $value
     *
     * @return string[]|string|null
     *
     * @note This implementation needs to be discussed as the caughtByPatterns might return a string[],
     *        for now getType doesn't support returning array.
     */
    protected function getTypes(&$value)
    {
        $items = $this->caughtByPatterns($value);

        if (count($items) === 0) {
            return null;
        }

        if (count($items) === 1) {
            return (string) array_values($items)[0];
        }

        return $items;
    }
}
