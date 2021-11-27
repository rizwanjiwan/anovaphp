<?php

namespace rizwanjiwan\anovaphp;

use rizwanjiwan\anovaphp\exceptions\TypeException;

/**
 * Helps with converting a duration
 */
class Duration
{

    public int $totalSeconds;
    public int $hrs;
    public int $min;
    public int $sec;

    /**
     * Create instance from seconds. Will fill in all the public properties with the correct amount based on this
     * @param int $totalSeconds
     * @throws TypeException
     */
    public function __construct(int $totalSeconds)
    {
        if($totalSeconds<0){
            throw new TypeException('Invalid duration: '.$totalSeconds);
        }
        $this->totalSeconds=$totalSeconds;
        $this->hrs = floor($totalSeconds / 3600);
        $this->min = floor(($totalSeconds / 60) % 60);
        $this->sec = $totalSeconds % 60;
    }

    /**
     * Get teh total number of seconds from hours,min, seconds.
     * @param int $hours
     * @param int $min
     * @param int $seconds
     * @return int
     */
    public static function toSeconds(int $hours,int $min, int $seconds):int
    {
        return ($hours*60+$min)*60+$seconds;
    }

    public function __toString():string
    {
        return self::padNumber($this->hrs).":".self::padNumber($this->min).":".self::padNumber($this->sec)." (".$this->totalSeconds." seconds)\n";

    }

    /**
     * Pad a number with one zero in front if it's one digit.
     * @param int $int
     * @return string
     */
    private static function padNumber(int $int):string
    {
        if($int<10){
            return "0".$int;
        }
        return "".$int;
    }
}