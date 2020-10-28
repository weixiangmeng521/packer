<?php

namespace GAubry\Helpers;

/**
 * Display stats about number of "events", with cumulative sum and diff since last call,
 * and configurable delay between two stats displays.
 *
 * Sample message: 2 events/s (+10, ∑=245)
 *
 * Usage:
 *     $c = new Counter();
 *     $c->start(10, 2);
 *     while (! $c->isTimeLimitExceeded()) {
 *         // do processing…
 *         $c->inc(3);
 *         sleep(1);
 *         $msg = $c->doTic() and $logger->debug($msg);
 *     }
 *
 * Result:
 *     13:40:59, DEBUG: 3 events/s (+6, ∑=6)
 *     13:41:01, DEBUG: 3 events/s (+6, ∑=12)
 *     13:41:03, DEBUG: 3 events/s (+6, ∑=18)
 *     13:41:05, DEBUG: 3 events/s (+6, ∑=24)
 *     13:41:07, DEBUG: 3 events/s (+6, ∑=30)
 *
 */
class Counter
{
    /**
     * Counter's state.
     * Structure: [
     *     'ts_limit'        => (float) max execution timestamp,
     *     'lap_duration'    => (int)   seconds between 2 stats display
     *     'lap_start'       => (float) timestamp starting a lap, i.e. a period between 2 stats display,
     *     'lap_end'         => (float) timestamp ending a lap, i.e. a period between 2 stats display,
     *     'lap_nb_events'   => (int)   nb events received in current lap, restarting at 0 at each new lap
     *     'total_nb_events' => (int)   sum of all previous lap's events,
    ];
     * @var array
     */
    private $aData;

    /**
     * Message format, sprintf notation, where:
     * - %1$d stands for number of events/s
     * - %2$d stands for number of events since latest display
     * - %3$d stands for total events
     *
     * Default: '%1$d events/s (+%2$d, ∑=%3$d)'
     *
     * @var string
     */
    private $sFormat;

    /**
     * Constructor.
     *
     * @param string $sFormat
     */
    public function __construct($sFormat = '%1$d events/s (+%2$d, ∑=%3$d)')
    {
        $this->aData = [];
        $this->sFormat = $sFormat;
    }

    /**
     * Init data structure.
     *
     * @param int $iMaxDuration Max number of seconds waiting/processing events.
     * @param int $iHeartbeat Approximate desired delay in seconds between 2 stats diplay.
     * @return Counter $this
     * @see $aData
     */
    public function start($iMaxDuration, $iHeartbeat)
    {
        $fNow        = microtime(true);
        $this->aData = [
            'ts_limit'        => $fNow + $iMaxDuration,
            'lap_duration'    => $iHeartbeat,
            'lap_start'       => $fNow,
            'lap_end'         => $fNow + $iHeartbeat,
            'lap_nb_events'   => 0,
            'total_nb_events' => 0
        ];
        return $this;
    }

    /**
     * @return bool true iff max execution time limit is exceeded
     */
    public function isTimeLimitExceeded()
    {
        return microtime(true) >= $this->aData['ts_limit'];
    }

    /**
     * @param int $iNumber add nb of events to 'nb_events_in_lap' counter.
     * @return Counter $this
     */
    public function inc($iNumber)
    {
        $this->aData['lap_nb_events']   += $iNumber;
        $this->aData['total_nb_events'] += $iNumber;
        return $this;
    }

    /**
     * Display stats about number of processed events since last display.
     */
    public function doTic()
    {
        $aData = &$this->aData;
        $fNow = microtime(true);

        if ($fNow >= $aData['lap_end'] || $this->isTimeLimitExceeded()) {
            $iNbEventsPerSec = round($aData['lap_nb_events'] / ($fNow - $aData['lap_start']));
            $sMsg = sprintf(
                $this->sFormat,
                $iNbEventsPerSec,
                $aData['lap_nb_events'],
                $aData['total_nb_events']
            );
            $aData['lap_start'] = $fNow;
            $aData['lap_end'] += $aData['lap_duration'] * ceil(($fNow - $aData['lap_end']) / $aData['lap_duration']);
            $aData['lap_nb_events'] = 0;
        } else {
            $sMsg = '';
        }

        return $sMsg;
    }
}
