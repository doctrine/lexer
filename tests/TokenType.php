<?php

declare(strict_types=1);

namespace Doctrine\Tests\Common\Lexer;

enum TokenType
{
    case INT;
    case OPERATOR;
    case STRING;
}
