<?php

namespace GAubry\Logger\Tests;

use Psr\Log\LogLevel;

class AbstractLoggerTest extends \PHPUnit_Framework_TestCase
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
     * @covers \GAubry\Logger\AbstractLogger::__construct
     */
    public function testConstructThrowExceptionWhenBadMinMsgLevel ()
    {
        $this->setExpectedException(
            '\Psr\Log\InvalidArgumentException',
            "Unkown level: 'xyz'! Level MUST be defined in \Psr\Log\LogLevel class."
        );
        $oMock = $this->getMock('\GAubry\Logger\AbstractLogger', array(), array(), '', false);
        $oMethod = new \ReflectionMethod('\GAubry\Logger\AbstractLogger', '__construct');
        $oMethod->setAccessible(true);
        $oMethod->invoke($oMock, 'xyz');
    }

    /**
     * @covers \GAubry\Logger\AbstractLogger::__construct
     * @dataProvider dataProviderAllMsgLevel
     */
    public function testConstructWithCorrectMinMsgLevel ($sLevel)
    {
        $oMock = $this->getMock('\GAubry\Logger\AbstractLogger', array(), array(), '', false);
        $oMethod = new \ReflectionMethod('\GAubry\Logger\AbstractLogger', '__construct');
        $oMethod->setAccessible(true);
        $oMethod->invoke($oMock, $sLevel);
        $this->assertTrue(true);
    }

    /**
     * @covers \GAubry\Logger\AbstractLogger::checkMsgLevel
     */
    public function testCheckMsgLevelThrowExceptionWhenBadMinMsgLevel ()
    {
        $this->setExpectedException(
            '\Psr\Log\InvalidArgumentException',
            "Unkown level: 'xyz'! Level MUST be defined in \Psr\Log\LogLevel class."
        );
        $oMock = $this->getMock('\GAubry\Logger\AbstractLogger', array(), array(), '', false);
        $oMethod = new \ReflectionMethod('\GAubry\Logger\AbstractLogger', 'checkMsgLevel');
        $oMethod->setAccessible(true);
        $oMethod->invoke($oMock, 'xyz');
    }

    /**
     * @covers \GAubry\Logger\AbstractLogger::checkMsgLevel
     * @dataProvider dataProviderAllMsgLevel
     *
     * @param string $sLevel
     */
    public function testCheckMsgLevelWithCorrectMinMsgLevel ($sLevel)
    {
        $oMock = $this->getMock('\GAubry\Logger\AbstractLogger', array(), array(), '', false);
        $oMethod = new \ReflectionMethod('\GAubry\Logger\AbstractLogger', 'checkMsgLevel');
        $oMethod->setAccessible(true);
        $oMethod->invoke($oMock, $sLevel);
        $this->assertTrue(true);
    }

    /**
     * Data provider of testCheckMsgLevelWithCorrectMinMsgLevel() and testConstructWithCorrectMinMsgLevel().
     *
     * @return array()
     */
    public function dataProviderAllMsgLevel ()
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
     * @covers \GAubry\Logger\AbstractLogger::interpolateContext
     * @dataProvider dataProviderTestInterpolateContext
     *
     * @param string $sMessage
     * @param array $aContext
     * @param string $sExpected
     */
    public function testInterpolateContext ($sMessage, array $aContext, $sExpected)
    {
        $oMock = $this->getMock('\GAubry\Logger\AbstractLogger', array(), array(), '', false);
        $oMethod = new \ReflectionMethod('\GAubry\Logger\AbstractLogger', 'interpolateContext');
        $oMethod->setAccessible(true);
        $sResult = $oMethod->invoke($oMock, $sMessage, $aContext);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * Data provider of testInterpolateContext().
     *
     * @return array()
     */
    public function dataProviderTestInterpolateContext ()
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
