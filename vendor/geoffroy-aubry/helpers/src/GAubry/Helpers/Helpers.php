<?php

namespace GAubry\Helpers;

use GAubry\Helpers\Exception\ExitCodeException;
use SKleeschulte\Base32;

/**
 * Some helpers used in several personal packages.
 * @SuppressWarnings(TooManyMethods)
 *
 * Copyright (c) 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
 *
 * @copyright 2013 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * @license http://www.gnu.org/licenses/lgpl.html
 */
class Helpers
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Flatten a multidimensional array (keys are ignored).
     *
     * @param array $aArray
     * @return array a one dimensional array.
     * @see http://stackoverflow.com/a/1320156/1813519
     */
    public static function flattenArray (array $aArray)
    {
        $aFlattened = array();
        array_walk_recursive(
            $aArray,
            function ($mValue) use (&$aFlattened) {
                $aFlattened[] = $mValue;
            }
        );
        return $aFlattened;
    }

    /**
     * Returns the UTF-8 translation of the specified string, only if not already in UTF-8.
     *
     * @param string $str
     * @return string the UTF-8 translation of the specified string, only if not already in UTF-8.
     */
    public static function utf8Encode ($str)
    {
        return (preg_match('//u', $str) === 1 ? $str : utf8_encode($str));
    }

    /**
     * Executes the given shell command and returns an array filled with every line of output from the command.
     * Trailing whitespace, such as \n, is not included in this array.
     * On shell error (exit code <> 0), throws a \RuntimeException with error message..
     *
     * @param string $sCmd shell command
     * @param string $sOutputPath optional redirection of standard output
     * @param string $sErrorPath optional redirection of standard error
     * @param bool $bAppend true to append to specified files
     * @return array array filled with every line of output from the command
     * @throws \RuntimeException if shell error
     */
    public static function exec ($sCmd, $sOutputPath = '', $sErrorPath = '', $bAppend = false)
    {
        // set STDOUT and STDERR
        $sAppending = ($bAppend ? '>>' : '>');
        if (empty($sOutputPath)) {
            if (empty($sErrorPath)) {
                $sStreams = '2>&1';
            } else {
                $sStreams = "2$sAppending$sErrorPath";
            }
        } elseif (empty($sErrorPath)) {
            $sStreams = "2>&1 1$sAppending$sOutputPath";
        } else {
            $sStreams = "1$sAppending$sOutputPath 2$sAppending$sErrorPath";
        }

        // execute cmd
        $sFullCmd = "( $sCmd ) $sStreams";
        exec($sFullCmd, $aResult, $iReturnCode);

        // retrieve content of STDOUT and STDERR
        if (empty($sOutputPath)) {
            $aOutput = $aResult;
            if (empty($sErrorPath)) {
                $aError = $aResult;
            } else {
                $aError = file($sErrorPath, FILE_IGNORE_NEW_LINES);
            }
        } elseif (empty($sErrorPath)) {
            $aOutput = file($sOutputPath, FILE_IGNORE_NEW_LINES);
            $aError = $aResult;
        } else {
            $aOutput = file($sOutputPath, FILE_IGNORE_NEW_LINES);
            $aError = file($sErrorPath, FILE_IGNORE_NEW_LINES);
        }

        // result
        if ($iReturnCode !== 0) {
            throw new ExitCodeException(
                "Exit code not null: $iReturnCode. Result: '" . implode("\n", $aError) . "'",
                $iReturnCode
            );
        }
        return $aOutput;
    }

    /**
     * Remove all Bash color sequences from the specified string.
     *
     * @param string $sMsg
     * @return string specified string without any Bash color sequence.
     */
    public static function stripBashColors ($sMsg)
    {
        return preg_replace('/\x1B\[([0-9]{1,2}(;[0-9]{1,2}){0,2})?[m|K]/', '', $sMsg);
    }

    /**
     * Rounds specified value with precision $iPrecision as native round() function, but keep trailing zeros.
     *
     * @param float $fValue value to round
     * @param int $iPrecision the optional number of decimal digits to round to (can also be negative)
     * @return string
     */
    public static function round ($fValue, $iPrecision = 0)
    {
        $sPrintfPrecision = max(0, $iPrecision);
        return sprintf("%01.{$sPrintfPrecision}f", round($fValue, $iPrecision));
    }

    /**
     * Returns a string with the first character of each word in specified string capitalized,
     * if that character is alphabetic.
     * Additionally, each character that is immediately after one of $aDelimiters will be capitalized too.
     *
     * @param string $sString
     * @param array $aDelimiters
     * @return string
     */
    public static function ucwordWithDelimiters ($sString, array $aDelimiters = array())
    {
        $sReturn = ucwords($sString);
        foreach ($aDelimiters as $sDelimiter) {
            if (strpos($sReturn, $sDelimiter) !== false) {
                $sReturn = implode($sDelimiter, array_map('ucfirst', explode($sDelimiter, $sReturn)));
            }
        }
        return $sReturn;
    }

    /**
     * Returns specified value in the most appropriate unit, with that unit.
     * If $bBinaryPrefix is FALSE then use SI units (i.e. k, M, G, T),
     * else use IED units (i.e. Ki, Mi, Gi, Ti).
     * @see http://en.wikipedia.org/wiki/Binary_prefix
     *
     * @param int $iValue
     * @param bool $bBinaryPrefix
     * @return array a pair constituted by specified value in the most appropriate unit and that unit
     */
    public static function intToMultiple ($iValue, $bBinaryPrefix = false)
    {
        static $aAllPrefixes = array(
            10 => array(12 => 'T', 9 => 'G', 6 => 'M', 3 => 'k', 0 => ''),
            2 => array(40 => 'Ti', 30 => 'Gi', 20 => 'Mi', 10 => 'Ki', 0 => ''),
        );

        $iBase = ($bBinaryPrefix ? 2 : 10);
        $aPrefixes = $aAllPrefixes[$iBase];
        $iMaxMultiple = 0;
        foreach (array_keys($aPrefixes) as $iMultiple) {
            if ($iValue >= pow($iBase, $iMultiple)) {
                $iMaxMultiple = $iMultiple;
                break;
            }
        }

        return array($iValue / pow($iBase, $iMaxMultiple), $aPrefixes[$iMaxMultiple]);
    }

    /**
     * Format a number with grouped thousands.
     * It is an extended version of number_format() that allows do not specify $decimals.
     *
     * @param float $fNumber The number being formatted.
     * @param string $sDecPoint Sets the separator for the decimal point.
     * @param string $sThousandsSep Sets the thousands separator. Only the first character of $thousands_sep is used.
     * @param int $iDecimals Sets the number of decimal points.
     * @return string A formatted version of $number.
     */
    public static function numberFormat ($fNumber, $sDecPoint = '.', $sThousandsSep = ',', $iDecimals = null)
    {
        if ($iDecimals !== null) {
            return number_format($fNumber, $iDecimals, $sDecPoint, $sThousandsSep);
        } else {
            $tmp = explode('.', $fNumber);
            $out = number_format($tmp[0], 0, $sDecPoint, $sThousandsSep);
            if (isset($tmp[1])) {
                $out .= $sDecPoint.$tmp[1];
            }
            return $out;
        }
    }

    /**
     * Formats a line passed as a fields array as CSV and return it, without the trailing newline.
     * Inspiration: http://www.php.net/manual/en/function.str-getcsv.php#88773
     *
     * @param array $aInput
     * @param string $sDelimiter
     * @param string $sEnclosure
     * @return string specified array converted into CSV format string
     */
    public static function strPutCSV ($aInput, $sDelimiter = ',', $sEnclosure = '"')
    {
        // Open a memory "file" for read/write...
        $hTmpFile = fopen('php://temp', 'r+');
        fputcsv($hTmpFile, $aInput, $sDelimiter, $sEnclosure);
        // ... rewind the "file" so we can read what we just wrote...
        rewind($hTmpFile);
        $sData = fgets($hTmpFile);
        fclose($hTmpFile);
        // ... and return the $data to the caller, with the trailing newline from fgets() removed.
        return rtrim($sData, "\n");
    }

    /**
     * array_merge_recursive() does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive(),
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     ⇒ array('key' => array('org value', 'new value'));
     *
     * arrayMergeRecursiveDistinct() does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * arrayMergeRecursiveDistinct(array('key' => 'org value'), array('key' => 'new value'));
     *     ⇒ array('key' => array('new value'));
     *
     * EVO on indexed arrays:
     *   Before:
     *     arrayMergeRecursiveDistinct(array('a', 'b'), array('c')) ⇒ array('c', 'b')
     *   Now:
     *     ⇒ array('c')
     *
     * @param array $aArray1
     * @param array $aArray2
     * @return array An array of values resulted from strictly merging the arguments together.
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @author Geoffroy Aubry
     * @see http://fr2.php.net/manual/en/function.array-merge-recursive.php#89684
     */
    public static function arrayMergeRecursiveDistinct (array $aArray1, array $aArray2)
    {
        $aMerged = $aArray1;
        if (self::isAssociativeArray($aMerged)) {
            foreach ($aArray2 as $key => &$value) {
                if (is_array($value) && isset($aMerged[$key]) && is_array($aMerged[$key])) {
                    $aMerged[$key] = self::arrayMergeRecursiveDistinct($aMerged[$key], $value);
                } else {
                    $aMerged[$key] = $value;
                }
            }
        } else {
            $aMerged = $aArray2;
        }
        return $aMerged;
    }

    /**
     * Returns TRUE iff the specified array is associative.
     * If the specified array is empty, then return FALSE.
     *
     * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
     *
     * @param array $aArray
     * @return bool true ssi iff the specified array is associative
     */
    public static function isAssociativeArray (array $aArray)
    {
        foreach (array_keys($aArray) as $key) {
            if (! is_int($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns current time with hundredths of a second.
     *
     * @param string $sFormat including %s for cs, eg: 'Y-m-d H:i:s.%s'
     * @return string current time with hundredths of a second.
     */
    public static function getCurrentTimeWithCS ($sFormat)
    {
        $aMicrotime = explode(' ', microtime());
        $sFilledFormat = sprintf($sFormat, $aMicrotime[0]*1000000);
        $sDate = date($sFilledFormat, $aMicrotime[1]);
        return $sDate;
    }

    /**
     * Returns 'Y-m-d H:i:s[.cs]' date to timestamp, where '.cs' stands for optional hundredths of a second.
     *
     * @param string $sDate at format 'Y-m-d H:i:s[.cs]'
     * @return float 'Y-m-d H:i:s[.cs]' date to timestamp, where '.cs' stands for optional hundredths of a second.
     */
    public static function dateTimeToTimestamp ($sDate)
    {
        if (strpos($sDate, '.') === false) {
            $sCS = '0';
        } else {
            $sCS = '0' . strstr($sDate, '.');
            $sDate = strstr($sDate, '.', true);
        }
        $aDate = explode(':', strtr($sDate, '- ', '::'));
        $iTs = mktime($aDate[3], $aDate[4], $aDate[5], $aDate[1], $aDate[2], (int)$aDate[0]);
        return $iTs + (float)$sCS;
    }

    /**
     * Generates a globally unique id generator using Mongo Object ID algorithm.
     *
     * The 12-byte ObjectId value consists of:
     * - a 4-byte value representing the seconds since the Unix epoch,
     * - a 3-byte machine identifier,
     * - a 2-byte process id, and
     * - a 3-byte counter, starting with a random value.
     * @see https://docs.mongodb.org/manual/reference/method/ObjectId/
     *
     * Uses SKleeschulte\Base32 because base_convert() may lose precision on large numbers due to properties related
     * to the internal "double" or "float" type used.
     * @see http://php.net/manual/function.base-convert.php
     *
     * @param  int    $iTimestamp Default: time()
     * @param  bool   $bBase32 Base32 (RFC 4648) or hex output?
     * @return string 20 base32-char or 24 hex-car MongoId.
     *
     * @see https://www.ietf.org/rfc/rfc4648.txt
     * @see http://stackoverflow.com/questions/14370143/create-mongodb-objectid-from-date-in-the-past-using-php-driver
     */
    public static function generateMongoId ($iTimestamp = 0, $bBase32 = true)
    {
        static $inc = 0;
        if ($inc === 0) {
            // set with microseconds:
            $inc = (int)substr(microtime(), 2, 6);
        }

        $bin = sprintf(
            '%s%s%s%s',
            pack('N', $iTimestamp ?: time()),
            substr(md5(gethostname()), 0, 3),
            pack('n', posix_getpid()),
            substr(pack('N', $inc++), 1, 3)
        );

        return $bBase32 ? strtolower(Base32::encodeByteStr($bin, true)) : bin2hex($bin);
    }
}
