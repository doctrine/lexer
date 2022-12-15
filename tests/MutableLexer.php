<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

/** @extends AbstractLexer<int, string> */
class MutableLexer extends AbstractLexer
{
    /** @var string[] */
    private array $catchablePatterns = [];

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

    protected function getType(string &$value): int
    {
        return 1;
    }
}
