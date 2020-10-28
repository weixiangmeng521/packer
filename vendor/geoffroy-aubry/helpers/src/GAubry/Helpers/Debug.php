<?php

namespace GAubry\Helpers;

/**
 * Debug class useful for don't forgetting where debug traces are.
 *
 * Automatically decorates print_r() and var_dump() with following information:
 *   – file and line of the caller
 *   – name of function/method containing the call
 *   – name of the parameter passed during call
 *
 * Example:
 * <code>
 *   function f($value) {Debug::printr($value);}
 * </code>
 *
 * Result:
 * <code>
 *   [function f() in file /path/to/file.php, line 31]
 *   $value =
 *   Array
 *   (
 *       [0] => 'xyz'
 *   )
 * </code>
 *
 * See examples/debug.php for a complete example.
 */
class Debug
{

    /**
     * Patterns for both HTML and CLI rendering:
     *   %1$s = function name or '∅' if no function
     *   %2$s = filename,
     *   %3$d = line in filename,
     *   %4$s = varname in parameter,
     *   %5$s = value of parameter
     *
     * @var array
     * @see self::displayTrace()
     */
    public static $sDisplayPatterns = array(
        'html' => '<pre><i>[function %1$s in file %2$s, line %3$d]</i><br /><b>%4$s</b> = %5$s</pre>',
        // @codingStandardsIgnoreStart HEREDOC syntax is not supported in array by pdepend…
        'cli'  => "\033[2;33;40m[function \033[1m%1\$s\033[22m in file \033[1m%2\$s\033[22m, line \033[1m%3\$d\033[22m]\n\033[1m%4\$s\033[22m = \033[0m\n%5\$s\n"
        // @codingStandardsIgnoreEnd
    );

    /**
     * Constructor.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Returns an array containing function name, filename and line in filename of the caller.
     * If called out of any function, then return '' as function name.
     * To return the caller of your function, either call get_caller(), or get_caller(__FUNCTION__).
     *
     * @see http://stackoverflow.com/a/4767754
     * @author Aram Kocharyan
     * @author Geoffroy Aubry
     *
     * @param string $sFunctionName function whose caller is searched
     * @param array $aStack trace, or debug_backtrace() by default
     * @return array triplet: (string)function name or '', (string)filename or '', (int)line in filename or 0
     */
    public static function getCaller ($sFunctionName = '', $aStack = array())
    {
        if ($aStack == array()) {
            $aStack = debug_backtrace();
        }

        if ($sFunctionName == '') {
            // We need $sFunctionName to be a function name to retrieve its caller. If it is omitted, then
            // we need to first find what function called get_caller(), and substitute that as the
            // default $sFunctionName. Remember that invoking get_caller() recursively will add another
            // instance of it to the function stack, so tell get_caller() to use the current stack.
            list($sFunctionName, , ) = self::getCaller(__FUNCTION__, $aStack);
        }

        // If we are given a function name as a string, go through the function stack and find
        // it's caller.
        for ($i = 0; $i < count($aStack); $i++) {
            $aCurrFunction = $aStack[$i];
            // Make sure that a caller exists, a function being called within the main script won't have a caller.
            if ($aCurrFunction['function'] == $sFunctionName && ($i + 1) < count($aStack)) {
                if (preg_match("/^(.*?)\((\d+)\) : eval\(\)\\'d code$/i", $aStack[$i]['file'], $aMatches) === 1) {
                    return array('eval', $aMatches[1], $aMatches[2]);
                } else {
                    return array($aStack[$i + 1]['function'], $aStack[$i]['file'], $aStack[$i]['line']);
                }
            }
        }

        // If out of any function:
        if ($aCurrFunction['function'] == $sFunctionName) {
            return array('', $aCurrFunction['file'], $aCurrFunction['line']);
        } else {
            // At this stage, no caller has been found, bummer.
            return array('', '', 0);
        }
    }

    /**
     * Return the name of the first parameter of the penultimate function call.
     *
     * TODO bug if multiple calls in the same line…
     *
     * @see http://stackoverflow.com/a/6837836
     * @author Sebastián Grignoli
     * @author Geoffroy Aubry
     *
     * @param string $sFunction function called
     * @param string $sFile file containing a call to $sFunction
     * @param int $iLine line in $sFile containing a call to $sFunction
     * @return string the name of the first parameter of the penultimate function call.
     */
    private static function getVarName ($sFunction, $sFile, $iLine)
    {
        $sContent = file($sFile);
        $sLine = $sContent[$iLine - 1];
        preg_match("#$sFunction\s*\((.+)\)#", $sLine, $aMatches);

        // Let's count brackets to see how many of them actually belongs to the var name.
        // e.g.:    die(catch_param($this->getUser()->hasCredential("delete")));
        // We want: $this->getUser()->hasCredential("delete")
        $iMax = strlen($aMatches[1]);
        $sVarname = '';
        $iNb = 0;
        for ($i = 0; $i < $iMax; $i++) {
            $char = substr($aMatches[1], $i, 1);
            if ($char == '(') {
                $iNb++;
            } elseif ($char == ')') {
                $iNb--;
                if ($iNb < 0) {
                    break;
                }
            }
            $sVarname .= $char;
        }

        // $varname now holds the name of the passed variable ('$' included)
        // e.g.: catch_param($hello)
        //             => $sVarname = "$hello"
        // or the whole expression evaluated
        // e.g.: catch_param($this->getUser()->hasCredential("delete"))
        //             => $sVarname = "$this->getUser()->hasCredential(\"delete\")"
        return $sVarname;
    }

    /**
     * Use specified pattern to display function name, filename, line in filename,
     * varname in parameter and value of this parameter of the caller.
     *
     * @param string $sPattern key of self::$sDisplayPatterns
     * @param string $sValue value of the parameter of the caller
     * @see self::$sDisplayPatterns
     */
    private static function displayTrace ($sPattern, $sValue)
    {
        list($sDebugFunction, , ) = self::getCaller();
        list($sFunction, $sFile, $sLine) = self::getCaller($sDebugFunction);
        $sFunction = (empty($sFunction) ? '∅' : "$sFunction()");
        $sVarName = self::getVarName($sDebugFunction, $sFile, $sLine);
        if (strlen($sFile) > 50) {
            $sFile = '...' . substr($sFile, -50);
        }
        echo sprintf(self::$sDisplayPatterns[$sPattern], $sFunction, $sFile, $sLine, $sVarName, $sValue);
    }

    /**
     * Display an HTML trace containing a var_dump() of the specified value.
     *
     * @param mixed $mValue value to pass to var_dump()
     */
    public static function htmlVarDump ($mValue)
    {
        ob_start();
        var_dump($mValue);
        $sOut = ob_get_contents();
        ob_end_clean();
        self::displayTrace('html', htmlspecialchars($sOut, ENT_QUOTES));
    }

    /**
     * Display an HTML trace containing a print_r() of the specified value.
     *
     * @param mixed $mValue value to pass to print_r()
     */
    public static function htmlPrintr ($mValue)
    {
        self::displayTrace('html', htmlspecialchars(print_r($mValue, true), ENT_QUOTES));
    }

    /**
     * Display a CLI trace containing a var_dump() of the specified value.
     *
     * @param mixed $mValue value to pass to var_dump()
     */
    public static function varDump ($mValue)
    {
        ob_start();
        var_dump($mValue);
        $sOut = ob_get_contents();
        ob_end_clean();
        self::displayTrace('cli', $sOut);
    }

    /**
     * Display a CLI trace containing a print_r() of the specified value.
     *
     * @param mixed $mValue value to pass to print_r()
     */
    public static function printr ($mValue)
    {
        self::displayTrace('cli', print_r($mValue, true));
    }
}
