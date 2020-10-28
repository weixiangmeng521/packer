<?php

namespace GAubry\Logger;

use GAubry\Helpers\Helpers;
use Psr\Log\LogLevel;

/**
 * PSR-3 logger for adding colors and indentation on PHP CLI output.
 *
 * Use tags and placeholder syntax to provide an easy way to color and indent PHP CLI output.
 * PSR-3 compatibility allows graceful degradation when switching to another PSR-3 compliant logger.
 * See README.md for more information.
 *
 * Copyright (c) 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
 *
 * @copyright 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * @license http://www.gnu.org/licenses/lgpl.html
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 */
class ColoredIndentedLogger extends AbstractLogger
{
    /**
     * Length of the indent tag.
     * @var int
     * @see 'indent_tag' key of ColoredIndentedLogger::$aDefaultConfig
     */
    private $iIndentTagLength;

    /**
     * Length of the unindent tag.
     * @var int
     * @see 'unindent_tag' key of ColoredIndentedLogger::$aDefaultConfig
     */
    private $iUnindentTagLength;

    /**
     * Current zero-based indentation level.
     * @var int
     */
    private $iIndentationLevel;

    /**
     * Full length color tags combining prefix to user define colors.
     * @var array
     * @see ColoredIndentedLogger::buildColorTags()
     * @see 'color_tag_prefix' key of ColoredIndentedLogger::$aDefaultConfig
     */
    private $aColorTags;

    /**
     * Default configuration.
     *   – 'colors'               => (array) Array of key/value pairs to associate bash color codes to color tags.
     *                                       Example: array(
     *                                           'debug'   => "\033[0;30m",
     *                                           'warning' => "\033[0;33m",
     *                                           'error'   => "\033[1;31m"
     *                                       )
     *   – 'base_indentation'     => (string) Describe what is a simple indentation, e.g. "\t".
     *   – 'indent_tag'           => (string) Tag usable at the start or at the end of the message to add
     *                                        one or more indentation level.
     *   – 'unindent_tag'         => (string) Tag usable at the start or at the end of the message to remove
     *                                        one or more indentation level.
     *   – 'min_message_level'    => (string) Threshold required to log message, must be defined in \Psr\Log\LogLevel.
     *   – 'reset_color_sequence' => (string) Concatenated sequence at the end of message when colors are used.
     *                                        For example: "\033[0m".
     *   – 'color_tag_prefix'     => (string) Prefix used in placeholders to distinguish standard context from colors.
     *
     * @var array
     */
    private static $aDefaultConfig = array(
        'colors'               => array(),
        'base_indentation'     => "\033[0;30m┆\033[0m   ",
        'indent_tag'           => '+++',
        'unindent_tag'         => '---',
        'min_message_level'    => LogLevel::DEBUG,
        'reset_color_sequence' => "\033[0m",
        'color_tag_prefix'     => 'C.'
    );

    /**
     * Current configuration.
     * @var array
     * @see ColoredIndentedLogger::$aDefaultConfig
     */
    private $aConfig;

    /**
     * Constructor.
     *
     * @param array $aConfig see self::$aDefaultConfig
     * @throws \Psr\Log\InvalidArgumentException if calling this method with a level not defined in \Psr\Log\LogLevel
     */
    public function __construct (array $aConfig = array())
    {
        $this->aConfig = Helpers::arrayMergeRecursiveDistinct(self::$aDefaultConfig, $aConfig);
        parent::__construct($this->aConfig['min_message_level']);

        $this->iIndentTagLength = strlen($this->aConfig['indent_tag']);
        $this->iUnindentTagLength = strlen($this->aConfig['unindent_tag']);
        $this->iIndentationLevel = 0;
        $this->buildColorTags();
    }

    /**
     * Build full length color tags by adding prefix to user define colors.
     * @see ColoredIndentedLogger::aColorTags
     * @see 'color_tag_prefix' key of ColoredIndentedLogger::$aDefaultConfig
     */
    private function buildColorTags ()
    {
        $this->aColorTags = array();
        foreach ($this->aConfig['colors'] as $sRawName => $sSequence) {
            $sName = '{' . $this->aConfig['color_tag_prefix'] . $sRawName . '}';
            $this->aColorTags[$sName] = $sSequence;
        }
    }

    /**
     * Update indentation level according to leading indentation tags
     * and remove them from the returned string.
     *
     * @param string $sMessage
     * @return string specified message without any leading indentation tag
     * @see ColoredIndentedLogger::iIndentationLevel
     */
    private function processLeadingIndentationTags ($sMessage)
    {
        $bTagFound = true;
        while ($bTagFound && strlen($sMessage) > 0) {
            if (substr($sMessage, 0, $this->iIndentTagLength) == $this->aConfig['indent_tag']) {
                $this->iIndentationLevel++;
                $sMessage = substr($sMessage, $this->iIndentTagLength);
            } elseif (substr($sMessage, 0, $this->iUnindentTagLength) == $this->aConfig['unindent_tag']) {
                $this->iIndentationLevel = max(0, $this->iIndentationLevel-1);
                $sMessage = substr($sMessage, $this->iUnindentTagLength);
            } else {
                $bTagFound = false;
            }
        }
        return $sMessage;
    }

    /**
     * Update indentation level according to trailing indentation tags
     * and remove them from the returned string.
     *
     * @param string $sMessage
     * @return string specified message without any trailing indentation tag
     * @see ColoredIndentedLogger::iIndentationLevel
     */
    private function processTrailingIndentationTags ($sMessage)
    {
        $bTagFound = true;
        while ($bTagFound && strlen($sMessage) > 0) {
            if (substr($sMessage, -$this->iIndentTagLength) == $this->aConfig['indent_tag']) {
                $this->iIndentationLevel++;
                $sMessage = substr($sMessage, 0, -$this->iIndentTagLength);
            } elseif (substr($sMessage, -$this->iUnindentTagLength) == $this->aConfig['unindent_tag']) {
                $this->iIndentationLevel = max(0, $this->iIndentationLevel-1);
                $sMessage = substr($sMessage, 0, -$this->iUnindentTagLength);
            } else {
                $bTagFound = false;
            }
        }
        return $sMessage;
    }

    /**
     * Logs with an arbitrary level.
     *
     * Allows adjustment of the indentation whith multiple leading or trailing tags:
     * see $this->sIndentTag and $this->sUnindentTag
     *
     * Allows insertion of bash colors via placeholders and context array.
     *
     * @param mixed $sMsgLevel message level, must be defined in \Psr\Log\LogLevel
     * @param string $sMessage message with placeholders
     * @param array $aContext context array
     * @throws \Psr\Log\InvalidArgumentException if calling this method with a level not defined in \Psr\Log\LogLevel
     */
    public function log ($sMsgLevel, $sMessage, array $aContext = array())
    {
        $this->checkMsgLevel($sMsgLevel);
        if (self::$aIntLevels[$sMsgLevel] >= $this->iMinMsgLevel) {
            $sMessage = $this->processLeadingIndentationTags($sMessage);
            $iCurrIndentationLvl = $this->iIndentationLevel;
            $sMessage = $this->processTrailingIndentationTags($sMessage);

            if (strlen($sMessage) > 0) {
                if (isset($this->aConfig['colors'][$sMsgLevel])
                    || isset($aContext[$this->aConfig['color_tag_prefix'] . $sMsgLevel])
                ) {
                    $sImplicitColor = '{' . $this->aConfig['color_tag_prefix'] . $sMsgLevel . '}';
                    $sMessage = $sImplicitColor . $sMessage;
                } else {
                    $iNbColorTags = preg_match_all('/{C.[A-Za-z0-9_.]+}/', $sMessage, $aMatches);
                    $sImplicitColor = '';
                }
                $sMessage = $this->interpolateContext($sMessage, $aContext);
                $sIndent = str_repeat($this->aConfig['base_indentation'], $iCurrIndentationLvl);
                $sMessage = $sIndent . str_replace("\n", "\n$sIndent$sImplicitColor", $sMessage);
                $sMessage = strtr($sMessage, $this->aColorTags);
                if ($sImplicitColor != ''
                    || (
                        $iNbColorTags > 0
                        && preg_match_all('/{C.[A-Za-z0-9_.]+}/', $sMessage, $aMatches) < $iNbColorTags
                    )
                ) {
                    $sMessage .= $this->aConfig['reset_color_sequence'];
                }

                echo $sMessage . PHP_EOL;
            }
        }
    }
}
