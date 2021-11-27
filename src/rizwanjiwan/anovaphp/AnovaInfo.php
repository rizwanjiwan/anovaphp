<?php

namespace rizwanjiwan\anovaphp;

use rizwanjiwan\anovaphp\exceptions\TypeException;

/**
 * Info about the anova
 */
class AnovaInfo
{

    CONST MODE_COOK=1;
    CONST MODE_IDLE=2;

    CONST TEMP_FAHRENHEIT=1;
    CONST TEMP_CELSIUS=2;
    /**
     * @var int One of MODE_COOK or MODE_IDLE
     */
    protected int $mode=self::MODE_IDLE;

    /**
     * @var int The unit of temperature the device displays
     */
    protected int $displayUnits=self::TEMP_CELSIUS;

    /**
     * @var float|int The target temperature in celsius
     */
    protected float|int $targetTemp=0;

    /**
     * @var float|int the water temp in celsius
     */
    protected float|int $waterTemp=0;

    /**
     * @var int The remaining cook time
     */
    protected int $remainingCookTime=0;

    /**
     * Set the mode
     * @param int $mode Either AnovaInfo::MODE_COOK or AnovaInfo::MODE_IDLE
     * @return $this
     * @throws TypeException
     */
    public function setMode(int $mode):self
    {
        if(($mode!==self::MODE_COOK)&&($mode!==self::MODE_IDLE)){
            throw new TypeException('Invalid mode: '.$mode);
        }
        $this->mode=$mode;
        return $this;
    }
    /**
     * Get the mode the cooker is in
     * @return int Either AnovaInfo::MODE_COOK or AnovaInfo::MODE_IDLE
     */
    public function getMode():int
    {
        return $this->mode;
    }

    /**
     * Set the target temperature
     * @param int|float $target the target
     * @param int $units Either AnovaInfo::TEMP_FAHRENHEIT or AnovaInfo::TEMP_CELSIUS
     * @throws TypeException
     */
    public function setTargetTemp(int|float $target, int $units):self
    {
        if($target<0){
            throw new TypeException('Can\'t have a negative temp: '.$target);
        }
        //don't need to check units, the toUnits function does that.
        $this->targetTemp=$this->toUnits($target,$units,self::TEMP_CELSIUS);
        return $this;
    }

    /**
     * Get the target temperature
     * @param int $units Either AnovaInfo::TEMP_FAHRENHEIT or AnovaInfo::TEMP_CELSIUS
     * @return int|float the temp in your specified units
     * @throws TypeException
     */
    public function getTargetTemp(int $units):int|float
    {
        //don't need to check units, the toUnits function does that.
        return $this->toUnits($this->targetTemp,self::TEMP_CELSIUS,$units);
    }

    /**
     * Get the units the device displays temperature in
     * @return int AnovaInfo::TEMP_CELSIUS or AnovaInfo::TEMP_FAHRENHEIT
     */
    public function getDisplayTempUnits():int
    {
        return $this->displayUnits;
    }

    /**
     * Set the temperature units that are used. Can't be written back to Anova.
     * @param int $units AnovaInfo::TEMP_CELSIUS or AnovaInfo::TEMP_FAHRENHEIT
     * @return AnovaInfo
     * @throws TypeException
     */
    public function setDisplayTempUnits(int $units):self
    {
        if(($units!==self::TEMP_FAHRENHEIT)&&($units!==self::TEMP_CELSIUS)){  //error
            throw new TypeException('Invalid temp: '.$units);
        }
        $this->displayUnits=$units;
        return $this;
    }
    /**
     * Get the water temp
     * @param int $units Either AnovaInfo::TEMP_FAHRENHEIT or AnovaInfo::TEMP_CELSIUS
     * @return int|float the temp in your specified units
     * @throws TypeException
     */
    public function getWaterTemp(int $units):int|float
    {
        //don't need to check units, the toUnits function does that.
        return $this->toUnits($this->waterTemp,self::TEMP_CELSIUS,$units);
    }
    /**
     * Get the water temp. Can't be written back to Anova.
     * @param int|float $temp temperature in celsius
     * @return self
     */
    public function setWaterTemp(int|float $temp):self
    {
        $this->waterTemp=$temp;
        return $this;
    }

    /**
     * Set the cook time
     * @param int $seconds Cook time in seconds
     * @return $this
     * @throws TypeException
     */
    public function setCookTime(int $seconds):self
    {
        if($seconds<0){
            throw new TypeException('Invalid duration: '.$seconds);
        }
        $this->remainingCookTime=$seconds;
        return $this;
    }

    /**
     * Get the remaining cook time
     * @return int remaining cook time in seconds
     */
    public function getCookTimeRemaining():int
    {
        return $this->remainingCookTime;
    }

    /**
     * Convert temperature units
     * @param int|float $number the target
     * @param int $currentUnits Either AnovaInfo::TEMP_FAHRENHEIT or AnovaInfo::TEMP_CELSIUS, what $number is in
     * @param int $targetUnits Either AnovaInfo::TEMP_FAHRENHEIT or AnovaInfo::TEMP_CELSIUS, what this function returns
     * @throws TypeException if any of the units are invalid
     */
    public static function toUnits(int|float $number, int $currentUnits, int $targetUnits):int|float
    {
        if($currentUnits===$targetUnits){   //nothing to do
            return $number;
        }
        //input check
        if(($currentUnits!==self::TEMP_FAHRENHEIT)&&($currentUnits!==self::TEMP_CELSIUS)){  //error
            throw new TypeException('Invalid temp: '.$currentUnits);
        }
        if(($targetUnits!==self::TEMP_FAHRENHEIT)&&($targetUnits!==self::TEMP_CELSIUS)){    //error
            throw new TypeException('Invalid temp: '.$targetUnits);
        }
        //convert to F?
        if($targetUnits===self::TEMP_FAHRENHEIT){
            return ($number*1.8) + 32;
        }
        //convert to c
        return($number - 32) *0.5556;
    }

    /**
     * @throws TypeException
     */
    public function __toString(): string
    {
        $displayTempUnits=$this->getTemperatureDisplayUnitString();
        $str='Mode: '.($this->mode===self::MODE_COOK?"Cooking":"Idle")."\n";
        $str.='Target: '.self::toUnits($this->targetTemp,self::TEMP_CELSIUS,$this->displayUnits).$displayTempUnits."\n";
        $str.='Water: '.self::toUnits($this->waterTemp,self::TEMP_CELSIUS,$this->displayUnits).$displayTempUnits."\n";
        $duration=new Duration($this->remainingCookTime);
        $str.='Time: '. $duration."\n";
        return $str;
    }
    public function getTemperatureDisplayUnitString():string
    {
        if($this->displayUnits===self::TEMP_FAHRENHEIT){
            return '°F';
        }
        return '°C';
    }
}