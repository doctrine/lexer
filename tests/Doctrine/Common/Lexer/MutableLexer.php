<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

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
    protected function getCatchablePatterns(): array
    {
        return $this->catchablePatterns;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns(): array
    {
        return ['[\s,]+'];
    }

    /**
     * {@inheritDoc}
     */
    protected function getType(&$value): int
    {
        return 1;
    }
}
