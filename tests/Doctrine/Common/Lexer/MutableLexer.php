<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

/** @extends AbstractLexer<int> */
class MutableLexer extends AbstractLexer
{
    /** @var string[] */
    private $catchablePatterns = [];

    public function addCatchablePattern(string $pattern): void
    {
        $this->catchablePatterns[] = $pattern;
    }

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
        return ['[\s,]+'];
    }

    /**
     * {@inheritDoc}
     */
    protected function getType(&$value)
    {
        return 1;
    }
}
