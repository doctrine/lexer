DQL Lexer
=========

Here is a more complicated example from the Doctrine ORM project.
The ``Doctrine\ORM\Query\Lexer`` implementation for DQL looks something
like the following:

.. code-block:: php

    use Doctrine\Common\Lexer\AbstractLexer;

    class Lexer extends AbstractLexer
    {
        // All tokens that are not valid identifiers must be < 100
        public const T_NONE              = 1;
        public const T_INTEGER           = 2;
        public const T_STRING            = 3;
        public const T_INPUT_PARAMETER   = 4;
        public const T_FLOAT             = 5;
        public const T_CLOSE_PARENTHESIS = 6;
        public const T_OPEN_PARENTHESIS  = 7;
        public const T_COMMA             = 8;
        public const T_DIVIDE            = 9;
        public const T_DOT               = 10;
        public const T_EQUALS            = 11;
        public const T_GREATER_THAN      = 12;
        public const T_LOWER_THAN        = 13;
        public const T_MINUS             = 14;
        public const T_MULTIPLY          = 15;
        public const T_NEGATE            = 16;
        public const T_PLUS              = 17;
        public const T_OPEN_CURLY_BRACE  = 18;
        public const T_CLOSE_CURLY_BRACE = 19;

        // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
        public const T_ALIASED_NAME         = 100;
        public const T_FULLY_QUALIFIED_NAME = 101;
        public const T_IDENTIFIER           = 102;

        // All keyword tokens should be >= 200
        public const T_ALL      = 200;
        public const T_AND      = 201;
        public const T_ANY      = 202;
        public const T_AS       = 203;
        public const T_ASC      = 204;
        public const T_AVG      = 205;
        public const T_BETWEEN  = 206;
        public const T_BOTH     = 207;
        public const T_BY       = 208;
        public const T_CASE     = 209;
        public const T_COALESCE = 210;
        public const T_COUNT    = 211;
        public const T_DELETE   = 212;
        public const T_DESC     = 213;
        public const T_DISTINCT = 214;
        public const T_ELSE     = 215;
        public const T_EMPTY    = 216;
        public const T_END      = 217;
        public const T_ESCAPE   = 218;
        public const T_EXISTS   = 219;
        public const T_FALSE    = 220;
        public const T_FROM     = 221;
        public const T_GROUP    = 222;
        public const T_HAVING   = 223;
        public const T_HIDDEN   = 224;
        public const T_IN       = 225;
        public const T_INDEX    = 226;
        public const T_INNER    = 227;
        public const T_INSTANCE = 228;
        public const T_IS       = 229;
        public const T_JOIN     = 230;
        public const T_LEADING  = 231;
        public const T_LEFT     = 232;
        public const T_LIKE     = 233;
        public const T_MAX      = 234;
        public const T_MEMBER   = 235;
        public const T_MIN      = 236;
        public const T_NEW      = 237;
        public const T_NOT      = 238;
        public const T_NULL     = 239;
        public const T_NULLIF   = 240;
        public const T_OF       = 241;
        public const T_OR       = 242;
        public const T_ORDER    = 243;
        public const T_OUTER    = 244;
        public const T_PARTIAL  = 245;
        public const T_SELECT   = 246;
        public const T_SET      = 247;
        public const T_SOME     = 248;
        public const T_SUM      = 249;
        public const T_THEN     = 250;
        public const T_TRAILING = 251;
        public const T_TRUE     = 252;
        public const T_UPDATE   = 253;
        public const T_WHEN     = 254;
        public const T_WHERE    = 255;
        public const T_WITH     = 256;

        /**
         * Creates a new query scanner object.
         *
         * @param string $input A query string.
         */
        public function __construct($input)
        {
            $this->setInput($input);
        }

        /**
         * {@inheritdoc}
         */
        protected function getCatchablePatterns()
        {
            return [
                '[a-z_][a-z0-9_]*\:[a-z_][a-z0-9_]*(?:\\\[a-z_][a-z0-9_]*)*', // aliased name
                '[a-z_\\\][a-z0-9_]*(?:\\\[a-z_][a-z0-9_]*)*', // identifier or qualified name
                '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?', // numbers
                "'(?:[^']|'')*'", // quoted strings
                '\?[0-9]*|:[a-z_][a-z0-9_]*', // parameters
            ];
        }

        /**
         * {@inheritdoc}
         */
        protected function getNonCatchablePatterns()
        {
            return ['\s+', '(.)'];
        }

        /**
         * {@inheritdoc}
         */
        protected function getType(&$value)
        {
            $type = self::T_NONE;

            switch (true) {
                // Recognize numeric values
                case (is_numeric($value)):
                    if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
                        return self::T_FLOAT;
                    }

                    return self::T_INTEGER;

                // Recognize quoted strings
                case ($value[0] === "'"):
                    $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));

                    return self::T_STRING;

                // Recognize identifiers, aliased or qualified names
                case (ctype_alpha($value[0]) || $value[0] === '_' || $value[0] === '\\'):
                    $name = 'Doctrine\ORM\Query\Lexer::T_' . strtoupper($value);

                    if (defined($name)) {
                        $type = constant($name);

                        if ($type > 100) {
                            return $type;
                        }
                    }

                    if (strpos($value, ':') !== false) {
                        return self::T_ALIASED_NAME;
                    }

                    if (strpos($value, '\\') !== false) {
                        return self::T_FULLY_QUALIFIED_NAME;
                    }

                    return self::T_IDENTIFIER;

                // Recognize input parameters
                case ($value[0] === '?' || $value[0] === ':'):
                    return self::T_INPUT_PARAMETER;

                // Recognize symbols
                case ($value === '.'):
                    return self::T_DOT;
                case ($value === ','):
                    return self::T_COMMA;
                case ($value === '('):
                    return self::T_OPEN_PARENTHESIS;
                case ($value === ')'):
                    return self::T_CLOSE_PARENTHESIS;
                case ($value === '='):
                    return self::T_EQUALS;
                case ($value === '>'):
                    return self::T_GREATER_THAN;
                case ($value === '<'):
                    return self::T_LOWER_THAN;
                case ($value === '+'):
                    return self::T_PLUS;
                case ($value === '-'):
                    return self::T_MINUS;
                case ($value === '*'):
                    return self::T_MULTIPLY;
                case ($value === '/'):
                    return self::T_DIVIDE;
                case ($value === '!'):
                    return self::T_NEGATE;
                case ($value === '{'):
                    return self::T_OPEN_CURLY_BRACE;
                case ($value === '}'):
                    return self::T_CLOSE_CURLY_BRACE;

                // Default
                default:
                    // Do nothing
            }

            return $type;
        }
    }

This is roughly what the DQL Parser looks like that uses the above
Lexer implementation:

.. note::

    You can see the full implementation `here <https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Query/Parser.php>`_.

.. code-block:: php

    class Parser
    {
        private $lexer;

        public function __construct($dql)
        {
            $this->lexer = new Lexer();
            $this->lexer->setInput($dql);
        }

        // ...

        public function getAST()
        {
            // Parse & build AST
            $AST = $this->QueryLanguage();

            // ...

            return $AST;
        }

        public function QueryLanguage()
        {
            $this->lexer->moveNext();

            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_SELECT:
                    $statement = $this->SelectStatement();
                    break;
                case Lexer::T_UPDATE:
                    $statement = $this->UpdateStatement();
                    break;
                case Lexer::T_DELETE:
                    $statement = $this->DeleteStatement();
                    break;
                default:
                    $this->syntaxError('SELECT, UPDATE or DELETE');
                    break;
            }

            // Check for end of string
            if ($this->lexer->lookahead !== null) {
                $this->syntaxError('end of string');
            }

            return $statement;
        }

        // ...
    }

Now the AST is used to transform the DQL query in to portable SQL for whatever relational
database you are using!

.. code-block:: php

    $parser = new Parser('SELECT u FROM User u');
    $AST = $parser->getAST(); // returns \Doctrine\ORM\Query\AST\SelectStatement

What is an AST?
===============

AST stands for `Abstract syntax tree <http://en.wikipedia.org/wiki/Abstract_syntax_tree>`_.
In computer science, an abstract syntax tree (AST), or just syntax tree, is a
tree representation of the abstract syntactic structure of source code written
in a programming language. Each node of the tree denotes a construct occurring in
the source code.
