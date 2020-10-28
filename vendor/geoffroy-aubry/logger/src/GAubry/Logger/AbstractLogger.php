<?php

namespace GAubry\Logger;

use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * This is a simple abstract Logger implementation that other Loggers can inherit from.
 *
 * Copyright (c) 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * @copyright 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * @license http://www.gnu.org/licenses/lgpl.html
 */
abstract class AbstractLogger extends \Psr\Log\AbstractLogger
{
    /**
     * Int value of threshold required to log message.
     * @var int
     * @see self::$aIntLevels
     */
    protected $iMinMsgLevel;

    /**
     * Describes log levels with priority.
     * PSR-3 Log levels are string-based…
     * @var array
     * @see \Psr\Log\LogLevel
     */
    protected static $aIntLevels = array(
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 10,
        LogLevel::NOTICE => 20,
        LogLevel::WARNING => 30,
        LogLevel::ERROR => 40,
        LogLevel::CRITICAL => 50,
        LogLevel::ALERT => 60,
        LogLevel::EMERGENCY => 70
    );

    /**
     * Constructor.
     *
     * @param string $iMinMsgLevel threshold required to log message, must be defined in \Psr\Log\LogLevel
     * @throws \Psr\Log\InvalidArgumentException if calling this method with a level not defined in \Psr\Log\LogLevel
    */
    protected function __construct ($sMinMsgLevel = LogLevel::DEBUG)
    {
        $this->checkMsgLevel($sMinMsgLevel);
        $this->iMinMsgLevel = self::$aIntLevels[$sMinMsgLevel];
    }

    /**
     * Check that specified $sMsgLevel is defined into \Psr\Log\LogLevel.
     *
     * @param string $sMsgLevel
     * @throws \Psr\Log\InvalidArgumentException if calling this method with a level not defined in \Psr\Log\LogLevel
     */
    protected function checkMsgLevel ($sMsgLevel)
    {
        if (! isset(self::$aIntLevels[$sMsgLevel])) {
            $sErrorMsg = "Unkown level: '$sMsgLevel'! Level MUST be defined in \Psr\Log\LogLevel class.";
            throw new InvalidArgumentException($sErrorMsg, 1);
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     * Taken from PSR-3's example implementation.
     *
     * Placeholder names MUST be delimited with a single opening brace { and a single closing brace }.
     * There MUST NOT be any whitespace between the delimiters and the placeholder name.
     * Placeholder names SHOULD be composed only of the characters A-Z, a-z, 0-9, underscore _, and period ..
     * The use of other characters is reserved for future modifications of the placeholders specification.
     *
     * Placeholders in the form {foo} will be replaced by the context data in key "foo".
     *
     * @param string $sMessage message with placeholders
     * @param array $aContext context array
     * @return string message whose placeholders are replaced by context values
     */
    protected function interpolateContext ($sMessage, array $aContext)
    {
        // build a replacement array with braces around the context keys
        $aReplace = array();
        foreach ($aContext as $sKey => $mValue) {
            $sValue = (string)$mValue;
            $aReplace['{' . trim($sKey) . '}'] = $sValue;
        }

        // interpolate replacement values into the message and return
        return strtr($sMessage, $aReplace);
    }
}
