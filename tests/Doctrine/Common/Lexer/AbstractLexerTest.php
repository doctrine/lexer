<?php

namespace Doctrine\Tests\Common\Lexer;

class AbstractLexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConcreteLexer
     */
    private $concreteLexer;

    public function setUp()
    {
        $this->concreteLexer = new ConcreteLexer();
    }

    public function dataProvider()
    {
        return array(
            array(
                'price=10',
                array(
                    array(
                        'value' => 'price',
                        'type' => 'string',
                        'position' => 0,
                    ),
                    array(
                        'value' => '=',
                        'type' => 'operator',
                        'position' => 5,
                    ),
                    array(
                        'value' => 10,
                        'type' => 'int',
                        'position' => 6,
                    ),
                ),
            ),
        );
    }

    public function testResetPeek()
    {
        $expectedTokens = array(
            array(
                'value' => 'price',
                'type' => 'string',
                'position' => 0,
            ),
            array(
                'value' => '=',
                'type' => 'operator',
                'position' => 5,
            ),
            array(
                'value' => 10,
                'type' => 'int',
                'position' => 6,
            ),
        );

        $this->concreteLexer->setInput('price=10');

        $this->assertEquals($expectedTokens[0], $this->concreteLexer->peek());
        $this->assertEquals($expectedTokens[1], $this->concreteLexer->peek());
        $this->concreteLexer->resetPeek();
        $this->assertEquals($expectedTokens[0], $this->concreteLexer->peek());
    }

    public function testResetPosition()
    {
        $expectedTokens = array(
            array(
                'value' => 'price',
                'type' => 'string',
                'position' => 0,
            ),
            array(
                'value' => '=',
                'type' => 'operator',
                'position' => 5,
            ),
            array(
                'value' => 10,
                'type' => 'int',
                'position' => 6,
            ),
        );

        $this->concreteLexer->setInput('price=10');
        $this->assertNull($this->concreteLexer->lookahead);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($expectedTokens[0], $this->concreteLexer->lookahead);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($expectedTokens[1], $this->concreteLexer->lookahead);

        $this->concreteLexer->resetPosition(0);

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->assertEquals($expectedTokens[0], $this->concreteLexer->lookahead);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input
     * @param $expectedTokens
     */
    public function testMoveNext($input, $expectedTokens)
    {
        $this->concreteLexer->setInput($input);
        $this->assertNull($this->concreteLexer->lookahead);

        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteLexer->moveNext());
            $this->assertEquals($expectedTokens[$i], $this->concreteLexer->lookahead);
        }

        $this->assertFalse($this->concreteLexer->moveNext());
        $this->assertNull($this->concreteLexer->lookahead);
    }


    public function testSkipUntil()
    {
        $this->concreteLexer->setInput('price=10');

        $this->assertTrue($this->concreteLexer->moveNext());
        $this->concreteLexer->skipUntil('operator');

        $this->assertEquals(
            array(
                'value' => '=',
                'type' => 'operator',
                'position' => 5,
            ),
            $this->concreteLexer->lookahead
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input
     * @param $expectedTokens
     */
    public function testPeek($input, $expectedTokens)
    {
        $this->concreteLexer->setInput($input);
        foreach ($expectedTokens as $expectedToken) {
            $this->assertEquals($expectedToken, $this->concreteLexer->peek());
        }

        $this->assertNull($this->concreteLexer->peek());
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input
     * @param $expectedTokens
     */
    public function testGlimpse($input, $expectedTokens)
    {
        $this->concreteLexer->setInput($input);

        foreach ($expectedTokens as $expectedToken) {
            $this->assertEquals($expectedToken, $this->concreteLexer->glimpse());
            $this->concreteLexer->moveNext();
        }

        $this->assertNull($this->concreteLexer->peek());
    }

    public function inputUntilPositionDataProvider()
    {
        return array(
            array('price=10', 5, 'price'),
        );
    }

    /**
     * @dataProvider inputUntilPositionDataProvider
     *
     * @param $input
     * @param $position
     * @param $expectedInput
     */
    public function testGetInputUntilPosition($input, $position, $expectedInput)
    {
        $this->concreteLexer->setInput($input);

        $this->assertSame($expectedInput, $this->concreteLexer->getInputUntilPosition($position));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input
     * @param $expectedTokens
     */
    public function testIsNextToken($input, $expectedTokens)
    {
        $this->concreteLexer->setInput($input);

        $this->concreteLexer->moveNext();
        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteLexer->isNextToken($expectedTokens[$i]['type']));
            $this->concreteLexer->moveNext();
        }
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $input
     * @param $expectedTokens
     */
    public function testIsNextTokenAny($input, $expectedTokens)
    {
        $allTokenTypes = array_map(function ($token) {
            return $token['type'];
        }, $expectedTokens);

        $this->concreteLexer->setInput($input);

        $this->concreteLexer->moveNext();
        for ($i = 0; $i < count($expectedTokens); $i++) {
            $this->assertTrue($this->concreteLexer->isNextTokenAny(array($expectedTokens[$i]['type'])));
            $this->assertTrue($this->concreteLexer->isNextTokenAny($allTokenTypes));
            $this->concreteLexer->moveNext();
        }
    }

    public function testGetLiteral()
    {
        $this->assertSame('Doctrine\Tests\Common\Lexer\ConcreteLexer::INT', $this->concreteLexer->getLiteral('int'));
        $this->assertSame('fake_token', $this->concreteLexer->getLiteral('fake_token'));
    }

    public function testIsA()
    {
        $this->assertTrue($this->concreteLexer->isA(11, 'int'));
        $this->assertTrue($this->concreteLexer->isA(1.1, 'int'));
        $this->assertTrue($this->concreteLexer->isA('=', 'operator'));
        $this->assertTrue($this->concreteLexer->isA('>', 'operator'));
        $this->assertTrue($this->concreteLexer->isA('<', 'operator'));
        $this->assertTrue($this->concreteLexer->isA('fake_text', 'string'));
    }
}
