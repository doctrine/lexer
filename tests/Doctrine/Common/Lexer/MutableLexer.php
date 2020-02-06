<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

class MutableLexer extends AbstractLexer
{
    /** @var string[] */
    private $catchablePatterns = [];

    /**
     * {@inheritDoc}
     */
    public function addCatchablePattern($pattern)
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
