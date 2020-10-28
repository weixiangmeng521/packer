<?php

namespace GAubry\Helpers\Tests;

use GAubry\Helpers\Debug;

class DebugTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
    }

    /**
     * @covers \GAubry\Helpers\Debug::varDump
     * @covers \GAubry\Helpers\Debug::getCaller
     * @covers \GAubry\Helpers\Debug::getVarName
     * @covers \GAubry\Helpers\Debug::displayTrace
     * @dataProvider dataProviderGeneric
     *
     * @param mixed $mValue
     */
    public function testVarDumpWithVariable ($mValue)
    {
        ob_start();
        var_dump($mValue);
        $sExpected = ob_get_contents();
        ob_end_clean();

        $sExpected = sprintf(
            Debug::$sDisplayPatterns['cli'],
            __FUNCTION__ . '()',
            strlen(__FILE__) > 50 ? '...' . substr(__FILE__, -50) : __FILE__,
            __LINE__ + 6,
            '$mValue',
            $sExpected
        );

        ob_start();
        Debug::varDump($mValue);
        $sResult = ob_get_contents();
        ob_end_clean();

        $sResult = str_replace("\033", '\033', $sResult);
        $sExpected = str_replace("\033", '\033', $sExpected);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * @covers \GAubry\Helpers\Debug::htmlVarDump
     * @dataProvider dataProviderGeneric
     *
     * @param mixed $mValue
     */
    public function testHtmlVarDumpWithVariable ($mValue)
    {
        ob_start();
        var_dump($mValue);
        $sExpected = ob_get_contents();
        ob_end_clean();

        $sExpected = sprintf(
            Debug::$sDisplayPatterns['html'],
            __FUNCTION__ . '()',
            strlen(__FILE__) > 50 ? '...' . substr(__FILE__, -50) : __FILE__,
            __LINE__ + 6,
            '$mValue',
            htmlspecialchars($sExpected, ENT_QUOTES)
        );

        ob_start();
        Debug::htmlVarDump($mValue);
        $sResult = ob_get_contents();
        ob_end_clean();

        $sResult = str_replace("\033", '\033', $sResult);
        $sExpected = str_replace("\033", '\033', $sExpected);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * @covers \GAubry\Helpers\Debug::printr
     * @dataProvider dataProviderGeneric
     *
     * @param mixed $mValue
     */
    public function testPrintrWithVariable ($mValue)
    {
        $sExpected = sprintf(
            Debug::$sDisplayPatterns['cli'],
            __FUNCTION__ . '()',
            strlen(__FILE__) > 50 ? '...' . substr(__FILE__, -50) : __FILE__,
            __LINE__ + 6,
            '$mValue',
            print_r($mValue, true)
        );

        ob_start();
        Debug::printr($mValue);
        $sResult = ob_get_contents();
        ob_end_clean();

        $sResult = str_replace("\033", '\033', $sResult);
        $sExpected = str_replace("\033", '\033', $sExpected);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * @covers \GAubry\Helpers\Debug::htmlPrintr
     * @dataProvider dataProviderGeneric
     *
     * @param mixed $mValue
     */
    public function testHtmlPrintrWithVariable ($mValue)
    {
        $sExpected = sprintf(
            Debug::$sDisplayPatterns['html'],
            __FUNCTION__ . '()',
            strlen(__FILE__) > 50 ? '...' . substr(__FILE__, -50) : __FILE__,
            __LINE__ + 6,
            '$mValue',
            htmlspecialchars(print_r($mValue, true), ENT_QUOTES)
        );

        ob_start();
        Debug::htmlPrintr($mValue);
        $sResult = ob_get_contents();
        ob_end_clean();

        $sResult = str_replace("\033", '\033', $sResult);
        $sExpected = str_replace("\033", '\033', $sExpected);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * Data provider pour testVarDumpWithVariable().
     * Data provider pour testHtmlVarDumpWithVariable().
     * Data provider pour testPrintrWithVariable().
     * Data provider pour testHtmlPrintrWithVariable().
     */
    public function dataProviderGeneric ()
    {
        return array(
            array('Hello!'),
            array((int)5),
            array(array('message' => 'Hello!')),
            array(new \stdClass()),
        );
    }

    /**
     * @covers \GAubry\Helpers\Debug::varDump
     * @covers \GAubry\Helpers\Debug::getCaller
     * @covers \GAubry\Helpers\Debug::getVarName
     */
    public function testVarDumpWithEval ()
    {
        $sValue = 'Hello!';
        ob_start();
        var_dump($sValue);
        $sExpected = ob_get_contents();
        ob_end_clean();

        $sExpected = sprintf(
            Debug::$sDisplayPatterns['cli'],
            'eval()',
            strlen(__FILE__) > 50 ? '...' . substr(__FILE__, -50) : __FILE__,
            __LINE__ + 6,
            "'$sValue'",
            $sExpected
        );

        ob_start();
        eval("\\GAubry\\Helpers\\Debug::varDump('Hello!');");
        $sResult = ob_get_contents();
        ob_end_clean();

        $sResult = str_replace("\033", '\033', $sResult);
        $sExpected = str_replace("\033", '\033', $sExpected);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * @covers \GAubry\Helpers\Debug::varDump
     * @covers \GAubry\Helpers\Debug::getVarName
     */
    public function testVarDumpWithValueAndParentheses ()
    {
        $sValue = 'Hello!()';
        ob_start();
        var_dump($sValue);
        $sExpected = ob_get_contents();
        ob_end_clean();

        $sExpected = sprintf(
            Debug::$sDisplayPatterns['cli'],
            __FUNCTION__ . '()',
            strlen(__FILE__) > 50 ? '...' . substr(__FILE__, -50) : __FILE__,
            __LINE__ + 6,
            "'$sValue'",
            $sExpected
        );

        ob_start();
        Debug::varDump('Hello!()');
        $sResult = ob_get_contents();
        ob_end_clean();

        $sResult = str_replace("\033", '\033', $sResult);
        $sExpected = str_replace("\033", '\033', $sExpected);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * @covers \GAubry\Helpers\Debug::getCaller
     */
    public function testGetCallerFromOutAnyFunction ()
    {
        $aTrace = array(
            array (
                'file'     => '/path/to/file',
                'line'     => 1,
                'function' => 'varDump',
                'class'    => 'GAubry\\Helpers\\Debug',
                'type'     => '::',
                'args'     => array (
                    0 => 'toto',
                ),
            )
        );
        $aResult = Debug::getCaller('varDump', $aTrace);
        $this->assertEquals(array('', '/path/to/file', 1), $aResult);
    }

    /**
     * @covers \GAubry\Helpers\Debug::getCaller
     */
    public function testGetCallerWithNoCallerFound ()
    {
        $aTrace = array(
            array (
                'file'     => '/path/to/file',
                'line'     => 1,
                'function' => 'unknown',
                'class'    => 'GAubry\\Helpers\\Debug',
                'type'     => '::',
                'args'     => array (
                    0 => 'toto',
                ),
            )
        );
        $aResult = Debug::getCaller('varDump', $aTrace);
        $this->assertEquals(array('', '', 0), $aResult);
    }
}
