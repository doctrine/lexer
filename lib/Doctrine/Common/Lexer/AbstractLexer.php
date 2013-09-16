<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Lexer;

/**
 * Base class for writing simple lexers, i.e. for creating small DSLs.
 *
 * @since  2.0
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractLexer
{
    /**
     * Array of scanned tokens.
     *
     * @var array
     */
    private $tokens = array();

    /**
     * Current lexer position in input string.
     *
     * @var integer
     */
    private $position = 0;

    /**
     * Current peek of current lexer position.
     *
     * @var integer
     */
    private $peek = 0;

    /**
     * The next token in the input.
     *
     * @var array
     */
    public $lookahead;

    /**
     * The last matched/seen token.
     *
     * @var array
     */
    public $token;

     /**
      * A stack of input strings kept for error reporting.
      *
      * @var array
      */
     protected $inputStack;

    /**
     * A stack of lexers kept for error reporting.
     *
     * @var array
     */
     static protected $lexerStack;

    /**
     * Sets the input data to be tokenized.
     *
     * The Lexer is immediately reset and the new input tokenized.
     * Any unprocessed tokens from any previous input are lost.
     *
     * @param string $input The input to be tokenized.
     *
     * @return void
     */
    public function setInput($input)
    {
        $this->tokens = array();
        $this->reset();
        $this->scan($input);
    }

    /**
     * Resets the lexer.
     *
     * @return void
     */
    public function reset()
    {
        $this->lookahead = null;
        $this->token = null;
        $this->peek = 0;
        $this->position = 0;
    }

    /**
     * Resets the peek pointer to 0.
     *
     * @return void
     */
    public function resetPeek()
    {
        $this->peek = 0;
    }

    /**
     * Resets the lexer position on the input to the given position.
     *
     * @param integer $position Position to place the lexical scanner.
     *
     * @return void
     */
    public function resetPosition($position = 0)
    {
        $this->position = $position;
    }

    /**
     * Checks whether a given token matches the current lookahead.
     *
     * @param integer|string $token
     *
     * @return boolean
     */
    public function isNextToken($token)
    {
        return null !== $this->lookahead && $this->lookahead['type'] === $token;
    }

    /**
     * Checks whether any of the given tokens matches the current lookahead.
     *
     * @param array $tokens
     *
     * @return boolean
     */
    public function isNextTokenAny(array $tokens)
    {
        return null !== $this->lookahead && in_array($this->lookahead['type'], $tokens, true);
    }

    /**
     * Moves to the next token in the input string.
     *
     * A token is an associative array containing three items:
     *  - 'value'    : the string value of the token in the input string
     *  - 'type'     : the type of the token (identifier, numeric, string, input
     *                 parameter, none)
     *  - 'position' : the position of the token in the input string
     *
     * @return array|null The next token; null if there is no more tokens left.
     */
    public function moveNext()
    {
        $this->peek = 0;
        $this->token = $this->lookahead;
        if (isset($this->tokens[$this->position])) {
            $this->lookahead = $this->tokens[$this->position++];
        } else {
            $this->lookahead = null;
            $this->\finish();
        }

        return $this->lookahead !== null;
    }

    /**
     * Tells the lexer to skip input tokens until it sees a token with the given value.
     *
     * @param string $type The token type to skip until.
     *
     * @return void
     */
    public function skipUntil($type)
    {
        while ($this->lookahead !== null && $this->lookahead['type'] !== $type) {
            $this->moveNext();
        }
    }

    /**
     * Checks if given value is identical to the given token.
     *
     * @param mixed   $value
     * @param integer $token
     *
     * @return boolean
     */
    public function isA($value, $token)
    {
        return $this->getType($value) === $token;
    }

    /**
     * Moves the lookahead token forward.
     *
     * @return array|null The next token or NULL if there are no more tokens ahead.
     */
    public function peek()
    {
        if (isset($this->tokens[$this->position + $this->peek])) {
            return $this->tokens[$this->position + $this->peek++];
        } else {
            return null;
        }
    }

    /**
     * Peeks at the next token, returns it and immediately resets the peek.
     *
     * @return array|null The next token or NULL if there are no more tokens ahead.
     */
    public function glimpse()
    {
        $peek = $this->peek();
        $this->peek = 0;
        return $peek;
    }

    /**
     * Scans the input string for tokens.
     *
     * @param string $input A query string.
     *
     * @return void
     */
    protected function scan($input)
    {
        static $regex;

        if ( ! isset($regex)) {
            $regex = '/(' . implode(')|(', $this->getCatchablePatterns()) . ')|'
                   . implode('|', $this->getNonCatchablePatterns()) . '/i';
        }

        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $matches = preg_split($regex, $input, -1, $flags);

        foreach ($matches as $match) {
            // Must remain before 'value' assignment since it can change content
            $type = $this->getType($match[0]);

            $this->tokens[] = array(
                'value' => $match[0],
                'type'  => $type,
                'position' => $match[1],
            );
        }
        // Add the information kept for error reporting to the stacks.
        self::pushLexer($this);
        $this->inputStack[] = $input;
    }

    /**
     * Removes the information kept for error reporting from the stacks.
     */
    protected function finish()
    {
        self::popLexer();
        array_pop($this->inputStack);
    }

    /**
     * Put a lexer on the stack.
     *
     * @param AbstractLexer $lexer
     *   The lexer to be saved.
     */
    static protected function pushLexer(AbstractLexer $lexer)
    {
        self::$lexerStack[] = $lexer;
    }

    /**
     * Remove and return the last lexer used.
     *
     * @return AbstractLexer
     *   The last lexer used.
     */
    static public function popLexer()
    {
        return array_pop(self::$lexerStack);
    }

    /**
     * Gets the literal for a given token.
     *
     * @param integer $token
     *
     * @return string
     */
    public function getLiteral($token)
    {
        $className = get_class($this);
        $reflClass = new \ReflectionClass($className);
        $constants = $reflClass->getConstants();

        foreach ($constants as $name => $value) {
            if ($value === $token) {
                return $className . '::' . $name;
            }
        }

        return $token;
    }

    /**
     * Get the original string read so far.
     *
     * @return
     *   The original string as read so far.
     */
    public function getOriginal()
    {
        return substr(array_pop($this->inputStack), 0, $this->lookahead['position']);
    }

    /**
     * Lexical catchable patterns.
     *
     * @return array
     */
    abstract protected function getCatchablePatterns();

    /**
     * Lexical non-catchable patterns.
     *
     * @return array
     */
    abstract protected function getNonCatchablePatterns();

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @param string $value
     *
     * @return integer
     */
    abstract protected function getType(&$value);
}
