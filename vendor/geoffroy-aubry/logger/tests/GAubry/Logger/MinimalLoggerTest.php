<?php

namespace GAubry\Logger\Tests;

use GAubry\Logger\MinimalLogger;
use Psr\Log\LogLevel;

class MinimalLoggerTest extends \PHPUnit_Framework_TestCase
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
     * @covers \GAubry\Logger\MinimalLogger::__construct
     */
    public function testConstructThrowExceptionWhenBadMinMsgLevel ()
    {
        $this->setExpectedException(
            '\Psr\Log\InvalidArgumentException',
            "Unkown level: 'xyz'! Level MUST be defined in \Psr\Log\LogLevel class."
        );
        $oLogger = new MinimalLogger('xyz');
    }

    /**
     * @covers \GAubry\Logger\MinimalLogger::log
     */
    public function testLogThrowExceptionWhenBadMinMsgLevel ()
    {
        $this->setExpectedException(
            '\Psr\Log\InvalidArgumentException',
            "Unkown level: 'xyz'! Level MUST be defined in \Psr\Log\LogLevel class."
        );
        $oLogger = new MinimalLogger(LogLevel::DEBUG);
        $oLogger->log('xyz', 'Message');
    }

    /**
     * @covers \GAubry\Logger\MinimalLogger::__construct
     * @covers \GAubry\Logger\MinimalLogger::log
     * @dataProvider dataProviderTestLogMinMsgLevel
     *
     * @param string $sMinMsgLevel
     * @param string $sLevel
     * @param string $sMessage
     * @param string $sExpectedMessage
     */
    public function testLogMinMsgLevel ($sMinMsgLevel, $sLevel, $sMessage, $sExpectedMessage)
    {
        $oLogger = new MinimalLogger($sMinMsgLevel);
        $sExpectedResult = $sExpectedMessage . (strlen($sExpectedMessage) > 0 ? PHP_EOL : '');
        $this->expectOutputString($sExpectedResult);
        $oLogger->log($sLevel, $sMessage);
    }

    /**
     * Data provider of testLogMinMsgLevel().
     *
     * @return array()
     */
    public function dataProviderTestLogMinMsgLevel ()
    {
        return array(
            array(LogLevel::DEBUG, LogLevel::DEBUG, 'Message', 'Message'),
            array(LogLevel::DEBUG, LogLevel::ERROR, 'Message', 'Message'),
            array(LogLevel::DEBUG, LogLevel::EMERGENCY, 'Message', 'Message'),

            array(LogLevel::ERROR, LogLevel::DEBUG, 'Message', ''),
            array(LogLevel::ERROR, LogLevel::ERROR, 'Message', 'Message'),
            array(LogLevel::ERROR, LogLevel::EMERGENCY, 'Message', 'Message'),

            array(LogLevel::EMERGENCY, LogLevel::DEBUG, 'Message', ''),
            array(LogLevel::EMERGENCY, LogLevel::ERROR, 'Message', ''),
            array(LogLevel::EMERGENCY, LogLevel::EMERGENCY, 'Message', 'Message'),
        );
    }

    /**
     * @covers \GAubry\Logger\MinimalLogger::log
     * @dataProvider dataProviderTestLogWithLevelSpecificMethods
     *
     * @param string $sLevel
     */
    public function testLogWithLevelSpecificMethods ($sLevel)
    {
        $sMessage = 'Message';
        $aContext = array('key' => 'value');
        $oMockLogger = $this->getMock('\GAubry\Logger\MinimalLogger', array('log'), array(LogLevel::DEBUG));
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
     * @return array()
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
     * @covers \GAubry\Logger\MinimalLogger::log
     * @dataProvider dataProviderTestLogWithContext
     *
     * @param string $sMessage
     * @param array $aContext
     * @param string $sExpectedMessage
     */
    public function testLogWithContext ($sMessage, array $aContext, $sExpectedMessage)
    {
        $oLogger = new MinimalLogger(LogLevel::DEBUG);
        $this->expectOutputString($sExpectedMessage . PHP_EOL);
        $oLogger->log(LogLevel::INFO, $sMessage, $aContext);
    }

    /**
     * Data provider of testLogWithContext().
     *
     * @return array()
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
}
