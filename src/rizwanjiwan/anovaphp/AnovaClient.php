<?php
namespace rizwanjiwan\anovaphp;

use rizwanjiwan\anovaphp\exceptions\AnovaException;
use rizwanjiwan\anovaphp\exceptions\TypeException;
use stdClass;

class AnovaClient
{

    CONST DEVICE_ID_TAG='{{DEVICE-ID}}';
    //usage example: device-info.http
    CONST DEVICE_INFO_URL='https://anovaculinary.io/devices/{{DEVICE-ID}}/states/?limit=1&max-age=10s';
    //usage example: get-auth-key-firebase.http
    CONST FIREBASE_AUTH_URL='https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyDQiOP2fTR9zvFcag2kSbcmG9zPh6gZhHw';
    //usage example: get-auth-key-anova.http
    CONST ANOVA_AUTH_URL='https://anovaculinary.io/authenticate';
    //usage exmaple: do-job.http
    CONST DO_JOB_URL='https://anovaculinary.io/devices/{{DEVICE-ID}}/current-job';

    CONST MODE_COOK_STR='COOK';
    CONST MODE_IDLE_STR='IDLE';
    CONST TEMP_FAHRENHEIT_STR='F';
    CONST TEMP_CELSIUS_STR='C';
    /**
     * @var string The authentication token
     */
    private string $authToken;
    /**
     * @var string The device id
     */
    private string $deviceId;

    /**
     * Create an instance from a token
     * @see AnovaClient.getToken() which you can store and pipe back in later
     * @param string $authToken an auth token
     * @param string $deviceId the device id
     */
    public function __construct(string $deviceId, string $authToken)
    {
        $this->authToken=$authToken;
        $this->deviceId=$deviceId;
    }

    /**
     * Get the authentication token from this intance you can use to create another instance in the future.
     * Not sure how long tokens last but I hear it's a year maybe?
     * @return string
     */
    public function getToken():string
    {
        return $this->authToken;
    }

    /**
     * @throws AnovaException
     * @throws TypeException
     */
    public function getInfo():AnovaInfo
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getURL(self::DEVICE_INFO_URL));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


        $headers = array();
        $headers[] = 'Accept: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new AnovaException('Error:' . curl_error($ch));
        }
        curl_close($ch);

        $resultObj=json_decode($result);
        //check reponse matches what we expect
        if(is_array($resultObj)===false){
            throw new AnovaException('Invalid json: '.$result);
        }
        $firstElement=$resultObj[0];
        if(property_exists($firstElement,'body')===false){
            throw new AnovaException('Missing body properties: '.json_encode($firstElement));
        }
        $firstElement=$firstElement->body;
        if((property_exists($firstElement,'job')===false)||
            (property_exists($firstElement->job,'target-temperature')===false)||
            (property_exists($firstElement->job,'temperature-unit')===false)||
            (property_exists($firstElement->job,'mode')===false)){
            throw new AnovaException('Missing job properties: '.json_encode($firstElement));
        }
        if((property_exists($firstElement,'job-status')===false)||
            (property_exists($firstElement->{'job-status'},'cook-time-remaining')===false)){
            throw new AnovaException('Missing job-status properties'.json_encode($firstElement));
        }
        if((property_exists($firstElement,'temperature-info')===false)||
            (property_exists($firstElement->{'temperature-info'},'water-temperature')===false)){
            throw new AnovaException('Missing temperature-info properties'.json_encode($firstElement));
        }
        //all checks out. load it into an info obj
        $info=new AnovaInfo();
        return $info
            ->setMode(self::modeStrToInt($firstElement->job->mode))
            ->setDisplayTempUnits(self::tempUnitLetterToInt($firstElement->job->{'temperature-unit'}))
            ->setTargetTemp($firstElement->job->{'target-temperature'},AnovaInfo::TEMP_CELSIUS)
            ->setCookTime($firstElement->{'job-status'}->{'cook-time-remaining'})
            ->setWaterTemp($firstElement->{'temperature-info'}->{'water-temperature'});

    }

    /**
     * Send a command to the anova
     * @param AnovaInfo $info the state you want the anova to be in
     * @throws TypeException
     * @throws AnovaException
     */
    public function setInfo(AnovaInfo $info)
    {

        $request=new stdClass();
        $request->{'cook-time-seconds'}=$info->getCookTimeRemaining();
        $request->{'target-temperature'}=$info->getTargetTemp(AnovaInfo::TEMP_CELSIUS);
        $request->{'temperature-unit'}=self::tempUnitIntToLetter($info->getDisplayTempUnits());
        $request->mode=self::modeIntToStr($info->getMode());
        $request->id=substr(sha1(mt_rand()),0,22);
        $request->{'ota-url'}='';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getURL(self::DO_JOB_URL));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$this->authToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new AnovaException("Error: ".curl_error($ch));
        }
        curl_close($ch);
    }


    /**
     * @throws AnovaException
     */
    public static function fromEmailAndPassword(string $deviceId, string $email, string $password):self
    {
        $anovaClient=new self($deviceId,'replaceme');

        //convert l/p into a token. Takes 2 calls. One to firebase and one to anova
        $idToken=$anovaClient->getTokenFromFirebase($email,$password);
        $anovaClient->authToken=$anovaClient->getTokenFromAnova($idToken);

        return $anovaClient;
    }

    /**
     * Get the token from anova. Needs device id set
     * @param string $idToken the token form getTokenFromFirebase
     * @return string the jwt
     * @throws AnovaException
     */
    private function getTokenFromAnova(string $idToken):string
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getURL(self::ANOVA_AUTH_URL));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Firebase-Token: '.$idToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new AnovaException('Error:' . curl_error($ch));
        }
        curl_close($ch);
        $resultObj=json_decode($result);
        if($resultObj===false){
            throw new AnovaException('Invalid json: '.$result);
        }
        if(property_exists($resultObj,'jwt')===false){
            throw new AnovaException('Missing jwt property. '.$result);
        }
        return $resultObj->jwt;
    }
    /**
     * Get the token from a login/password from firebase. Needs the device id set.
     * @param string $email
     * @param string $password
     * @return string
     * @throws AnovaException on error
     */
    private function getTokenFromFirebase(string $email, string $password):string
    {
        $requestObj=new stdClass();
        $requestObj->email=$email;
        $requestObj->password=$password;
        $requestObj->returnSecureToken=true;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getURL(self::FIREBASE_AUTH_URL));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestObj));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new AnovaException( 'Error:' . curl_error($ch));
        }
        curl_close($ch);
        $resultObj=json_decode($result);
        if($resultObj===false){
            throw new AnovaException('Invalid json: '.$result);
        }
        if(property_exists($resultObj,'idToken')===false){
            throw new AnovaException('Missing idToken property. '.$result);
        }
        return $resultObj->idToken;
    }

    /**
     * Get a url with the replacement text replaced so you can use it.
     * @param string $urlWithReplacementText url with _TAG items in it
     * @return string the url with the replacements replaced
     */
    private function getURL(string $urlWithReplacementText):string
    {
        return str_replace(self::DEVICE_ID_TAG,$this->deviceId,$urlWithReplacementText);
    }

    /**
     * Convert the F or C to the AnovaInfo int for units
     * @param string $letter
     * @return int
     */
    private static function tempUnitLetterToInt(string $letter):int
    {
        $letter=trim($letter);
        if(strcmp(self::TEMP_FAHRENHEIT_STR,$letter)===0){
            return AnovaInfo::TEMP_FAHRENHEIT;
        }
        return AnovaInfo::TEMP_CELSIUS;
    }

    /**
     * Convert an AnovaInfo unit int to the string for the API
     * @param int $int
     * @return string
     */
    private static function tempUnitIntToLetter(int $int):string
    {
        if($int===AnovaInfo::TEMP_FAHRENHEIT){
            return self::TEMP_FAHRENHEIT_STR;
        }
        return self::TEMP_CELSIUS_STR;
    }

    /**
     * Convert the Cook/idle to the AnovaInfo int for mode
     * @param string $string
     * @return int
     */
    private static function modeStrToInt(string $string):int
    {
        $string=trim($string);
        if(strcmp($string,self::MODE_COOK_STR)===0){
            return AnovaInfo::MODE_COOK;
        }
        return AnovaInfo::MODE_IDLE;
    }

    /**
     * Convert an AnovaInfo mode int to the string for the API
     * @param int $int
     * @return string
     */
    private static function modeIntToStr(int $int):string
    {
        if($int===AnovaInfo::MODE_COOK){
            return self::MODE_COOK_STR;
        }
        return self::MODE_IDLE_STR;
    }

}