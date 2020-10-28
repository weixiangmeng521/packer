<?php

namespace GAubry\Logger;

use Psr\Log\LogLevel;

/**
 * This Logger can be used to avoid both indentation and insertion of bash color codes into log messages.
 *
 * Logging should always be optional, and if no logger is provided to your
 * library creating a \Psr\Log\NullLogger instance in order to avoid conditional log calls.
 *
 * Copyright (c) 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
 *
 * @copyright 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * @license http://www.gnu.org/licenses/lgpl.html
 */
class MinimalLogger extends AbstractLogger
{
    /**
     * Constructor.
     *
     * @param string $iMinMsgLevel threshold required to log message, must be defined in \Psr\Log\LogLevel
     * @throws \Psr\Log\InvalidArgumentException if calling this method with a level not defined in \Psr\Log\LogLevel
     */
    public function __construct ($sMinMsgLevel = LogLevel::DEBUG)
    {
        parent::__construct($sMinMsgLevel);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $sMsgLevel message level, must be defined in \Psr\Log\LogLevel
     * @param string $sMessage message with placeholders
     * @param array $aContext context array
     * @throws \Psr\Log\InvalidArgumentException if calling this method with a level not defined in \Psr\Log\LogLevel
     */
    public function log ($sMsgLevel, $sMessage, array $aContext = array())
    {
        $this->checkMsgLevel($sMsgLevel);
        if (self::$aIntLevels[$sMsgLevel] >= $this->iMinMsgLevel) {
            echo $this->interpolateContext($sMessage, $aContext) . PHP_EOL;
        }
    }
}
