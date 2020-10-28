<?php

namespace GAubry\Logger\Tests;

use GAubry\Logger\ColoredIndentedLogger;
use Psr\Log\LogLevel;

class ColoredIndentedLoggerTest extends \PHPUnit_Framework_TestCase
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
     * @covers \GAubry\Logger\ColoredIndentedLogger::__construct
     */
    public function testConstructThrowExceptionWhenBadMinMsgLevel ()
    {
        $this->setExpectedException(
            '\Psr\Log\InvalidArgumentException',
            "Unkown level: 'xyz'! Level MUST be defined in \Psr\Log\LogLevel class."
        );
        $oLogger = new ColoredIndentedLogger(array('min_message_level' => 'xyz'));
    }


    /**
     * @covers \GAubry\Logger\ColoredIndentedLogger::log
     */
    public function testLogThrowExceptionWhenBadMinMsgLevel ()
    {
        $this->setExpectedException(
            '\Psr\Log\InvalidArgumentException',
            "Unkown level: 'xyz'! Level MUST be defined in \Psr\Log\LogLevel class."
        );
        $oLogger = new ColoredIndentedLogger(array());
        $oLogger->log('xyz', 'Message');
    }

    /**
     * @covers \GAubry\Logger\ColoredIndentedLogger::__construct
     * @covers \GAubry\Logger\ColoredIndentedLogger::log
     * @dataProvider dataProviderTestLogMinMsgLevel
     *
     * @param string $sMinMsgLevel
     * @param string $sLevel
     * @param string $sMessage
     * @param string $sExpectedMessage
     */
    public function testLogMinMsgLevel ($sMinMsgLevel, $sLevel, $sMessage, $sExpectedMessage)
    {
        $oLogger = new ColoredIndentedLogger(array('min_message_level' => $sMinMsgLevel));
        $sExpectedResult = $sExpectedMessage . (strlen($sExpectedMessage) > 0 ? PHP_EOL : '');
        $this->expectOutputString($sExpectedResult);
        $oLogger->log($sLevel, $sMessage);
    }

    /**
     * Data provider of testLogMinMsgLevel().
     *
     * @return array
     */
    public function dataProviderTestLogMinMsgLevel ()
    {
        $aLevels = array(
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY
        );

        $aTests = array();
        foreach ($aLevels as $idxMin => $iMinMsgLevel) {
            foreach ($aLevels as $idx => $iMsgLevel) {
                $sExpectedMsg = ($idxMin > $idx ? '' : 'Message');
                $aTests[] = array($iMinMsgLevel, $iMsgLevel, 'Message', $sExpectedMsg);
            }
        }
        return $aTests;
    }

    /**
     * @covers \GAubry\Logger\ColoredIndentedLogger::log
     * @dataProvider dataProviderTestLogWithLevelSpecificMethods
     *
     * @param string $sLevel
     */
    public function testLogWithLevelSpecificMethods ($sLevel)
    {
        $sMessage = 'Message';
        $aContext = array('key' => 'value');
        $oMockLogger = $this->getMock(
            '\GAubry\Logger\ColoredIndentedLogger',
            array('log'),
            array(array(), ' ', '+', '-', LogLevel::DEBUG)
        );
        $oMockLogger->expects($this->once())->method('log')->with(
            $this->equalTo($sLevel),
            $this->equalTo($sMessage),
            $this->equalTo($aContext)
        );
        $oMockLogger->$sLevel($sMessage, $aContext);
    }

    /**
     * Data provider of testLogWithLevelSpecificMethods().
     *
     * @return array
     */
    public function dataProviderTestLogWithLevelSpecificMethods ()
    {
        return array(
            array(LogLevel::DEBUG),
            array(LogLevel::INFO),
            array(LogLevel::NOTICE),
            array(LogLevel::WARNING),
            array(LogLevel::ERROR),
            array(LogLevel::CRITICAL),
            array(LogLevel::ALERT),
            array(LogLevel::EMERGENCY),
        );
    }

    /**
     * @covers \GAubry\Logger\ColoredIndentedLogger::log
     * @dataProvider dataProviderTestLogWithContext
     *
     * @param string $sMessage
     * @param array $aContext
     * @param string $sExpectedMessage
     */
    public function testLogWithContext ($sMessage, array $aContext, $sExpectedMessage)
    {
        $oLogger = new ColoredIndentedLogger(array(), ' ', '+', '-', LogLevel::DEBUG);
        if (strlen($sExpectedMessage) > 0) {
            $sExpectedMessage .= PHP_EOL;
        }
        $this->expectOutputString($sExpectedMessage);
        $oLogger->log(LogLevel::INFO, $sMessage, $aContext);
    }

    /**
     * Data provider of testLogWithContext().
     *
     * @return array
     */
    public function dataProviderTestLogWithContext ()
    {
        return array(
            array('', array(), ''),
            array('bla', array(), 'bla'),
            array('', array('param1' => 'toto'), ''),

            array('bla {param1}', array('param1' => 'toto'), 'bla toto'),
            array('bla {param1} bla', array('param1' => 'toto'), 'bla toto bla'),
            array('{param1} bla', array('param1' => 'toto'), 'toto bla'),

            array('bla {param12} bla', array('param1' => 'toto'), 'bla {param12} bla'),
            array('bla {param1} bla', array('param12' => 'toto'), 'bla {param1} bla'),

            array('bla {p1}{p1} bla', array('p1' => 'to'), 'bla toto bla'),
            array('{{p1}{p2}{p3}}', array('p1' => 'A', 'p2' => 'B', 'p3' => 'C'), '{ABC}'),
        );
    }

    /**
     * @covers \GAubry\Logger\ColoredIndentedLogger::log
     * @covers \GAubry\Logger\ColoredIndentedLogger::processLeadingIndentationTags
     * @covers \GAubry\Logger\ColoredIndentedLogger::processTrailingIndentationTags
     * @dataProvider dataProviderTestLogWithIndent
     *
     * @param array $aMessages
     * @param string $sExpectedMessage
     */
    public function testLogWithIndent (array $aMessages, $sExpectedMessage)
    {
        $aConfig = array(
            'base_indentation'     => '  ',
            'indent_tag'           => '+++',
            'unindent_tag'         => '---'
        );
        $oLogger = new ColoredIndentedLogger($aConfig);
        $this->expectOutputString($sExpectedMessage);
        foreach ($aMessages as $sMessage) {
            $oLogger->log(LogLevel::INFO, $sMessage, array());
        }
    }

    /**
     * Data provider of testLogWithIndent().
     *
     * @return array
     */
    public function dataProviderTestLogWithIndent ()
    {
        $N = PHP_EOL;
        return array(
            array(array(''), ''),
            array(array('bla'), "bla$N"),

            array(array('+++bla'), "  bla$N"),
            array(array('---bla'), "bla$N"),
            array(array('---'), ''),

            array(array('++++++bla'), "    bla$N"),
            array(array('+++---bla'), "bla$N"),
            array(array('---+++bla'), "  bla$N"),
            array(array('------+++bla'), "  bla$N"),
            array(array('+++------+++bla'), "  bla$N"),
            array(array('++++++---+++bla'), "    bla$N"),

            array(array('bla+++bla'), "bla+++bla$N"),
            array(array('bla---bla'), "bla---bla$N"),

            array(array('A+++', 'B'), "A$N  B$N"),
            array(array('A+++', '+++B'), "A$N    B$N"),
            array(array('A+++', '---B'), "A{$N}B$N"),

            array(array('A+++', 'B', '---C'), "A$N  B{$N}C$N"),
            array(array('A+++', 'B---', 'C'), "A$N  B{$N}C$N"),

            array(array('A+++', 'B', 'C---', 'D'), "A$N  B$N  C{$N}D$N"),
            array(array('A+++', 'B+++', '------', 'D'), "A$N  B{$N}D$N"),
            array(array('A+++', 'B+++', 'C', '---D'), "A$N  B$N    C$N  D$N"),
        );
    }

    /**
     * @covers \GAubry\Logger\ColoredIndentedLogger::log
     * @covers \GAubry\Logger\ColoredIndentedLogger::buildColorTags
     * @dataProvider dataProviderTestLogWithColor
     *
     * @param string $sLevelMsg
     * @param string $sMessage
     * @param string $sExpectedMessage
     */
    public function testLogWithColor ($sLevelMsg, $sMessage, $sExpectedMessage)
    {
        $aConfig = array(
            'colors' => array(
                'emergency' => '[RED]',
                'title' => '[WHITE]',
                'ok' => '[GREEN]',
            ),
            'base_indentation'     => '  ',
            'indent_tag'           => '+++',
            'unindent_tag'         => '---',
            'reset_color_sequence' => '[RESET]',
        );
        $oLogger = new ColoredIndentedLogger($aConfig);
        if (strlen($sExpectedMessage) > 0) {
            $sExpectedMessage .= PHP_EOL;
        }
        $this->expectOutputString($sExpectedMessage);
        $oLogger->log($sLevelMsg, $sMessage, array());
    }

    /**
     * Data provider of testLogWithColor().
     *
     * @return array
     */
    public function dataProviderTestLogWithColor ()
    {
        return array(
            array(LogLevel::INFO, '', ''),
            array(LogLevel::INFO, 'a', 'a'),
            array(LogLevel::EMERGENCY, 'a', '[RED]a[RESET]'),
            array(LogLevel::EMERGENCY, '+++a', '  [RED]a[RESET]'),
            array(LogLevel::INFO, 'result: {C.ok}OK', 'result: [GREEN]OK[RESET]'),
            array(LogLevel::INFO, '{C.title}result: {C.ok}OK', '[WHITE]result: [GREEN]OK[RESET]'),
            array(LogLevel::EMERGENCY, '{C.title}result: {C.ok}OK', '[RED][WHITE]result: [GREEN]OK[RESET]'),

            array(LogLevel::INFO, "a\nb", "a\nb"),
            array(LogLevel::INFO, "+++a\nb", "  a\n  b"),
            array(LogLevel::EMERGENCY, "a\nb", "[RED]a\n[RED]b[RESET]"),
            array(LogLevel::EMERGENCY, "+++a\nb", "  [RED]a\n  [RED]b[RESET]"),
        );
    }
}
