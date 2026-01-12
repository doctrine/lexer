<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;
use Override;

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
    #[Override]
    protected function getCatchablePatterns(): array
    {
        return $this->catchablePatterns;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function getNonCatchablePatterns(): array
    {
        return ['[\s,]+'];
    }

    #[Override]
    protected function getType(string &$value): int
    {
        return 1;
    }
}
