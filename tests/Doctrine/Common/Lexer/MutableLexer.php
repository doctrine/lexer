<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

use Doctrine\Common\Lexer\AbstractLexer;

class MutableLexer extends AbstractLexer
{
    /** @var string[] */
    private $catchablePatterns = [];

    public function addCatchablePattern($pattern)
    {
        $this->catchablePatterns[] = $pattern;
    }

    protected function getCatchablePatterns()
    {
        return $this->catchablePatterns;
    }

    protected function getNonCatchablePatterns()
    {
        return ['[\s,]+'];
    }

    protected function getType(&$value)
    {
        return 1;
    }
}
