<?php

use rizwanjiwan\anovaphp\AnovaClient;
use rizwanjiwan\anovaphp\AnovaInfo;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

date_default_timezone_set('America/Toronto');
require_once 'vendor/autoload.php';

//$client=new AnovaClient('QOvNpLVfXQ25a85wzU7l3S','eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJlbWFpbCI6InJpendhbkBqaXdhbi5jYSIsImV4cCI6MTY2OTQ4NDczMSwiaWF0IjoxNjM3OTQ4NzMxLCJpZCI6ImNSNktRVDhPUzFON0Q5SURJdGZ0S0E4UDl0QjIiLCJsYXN0X3VzZWRfc2lnbl9pbl9wcm92aWRlciI6InBhc3N3b3JkIiwibmFtZSI6IiIsInBpY3R1cmUiOiIiLCJ0eXBlIjoidXNlciJ9.70uDKgAVdk2eB44J6kloIF2u9OWm13Awk2n1_A3JQ88');
//echo $client->getInfo();

class Demo extends CLI
{

    protected function setup(Options $options)
    {
        $options->setHelp('A demo of the anovaphp library.');
        $options->registerArgument('DeviceID','Your device ID',true);
        $options->registerArgument('StartTime','Time you want to start your Anova',true);
        $options->registerArgument('Duration','How long do you want to run it at temperature (minutes)',true);
        $options->registerArgument('Temperature','The temperature (in your configured default)',true);
        $options->registerArgument('token','Token you previously generated with an email and password',false);
        $options->registerArgument('email','Account email if you don\'t have a token',false);
        $options->registerArgument('password','Your account password if you don\'t have a token',false);

    }

    protected function main(Options $options)
    {
        $args=$options->getArgs();
        $argsc=count($args);
        if($argsc<5){
            echo "Error: Missing arguments. Use php demo.php --help for more info\n";
            return;
        }
        if($argsc>6){
            echo "Error: Too many arguments. Use php demo.php --help for more info\n";
            return;
        }
        try {
            $client=null;
            echo "Starting at: ".date('Y-m-d H:i')."\n\n";
            $start=strtotime($args[1]);
            $duration=intval($args[2])*60;//to seconds
            $temp=intval($args[3]);
            if($argsc===5){//token
                $client=new AnovaClient($args[0],$args[4]);
            }
            else{//must be 6; email/password
                $client=AnovaClient::fromEmailAndPassword($args[0],$args[4],$args[5]);
                echo "Here's your token for a future run:\n\n".$client->getToken()."\n\n";
            }
            $info=$client->getInfo();
            echo "Current status\n";
            echo "---------------\n";
            echo $info;
            echo "\nWaiting till start time (".date('Y-m-d H:i',$start).")...";
            while($start>time()){
                sleep(60);
                try {
                    $info=$client->getInfo();//keep the anova awake
                }
                catch(Exception $e){
                    sleep(5);//try again
                    $info=$client->getInfo();
                }
                echo ".";
            }
            //go time!
            echo "\n\nGo time!\n\n";
            $info->setTargetTemp($temp,$info->getDisplayTempUnits());
            $info->setCookTime($duration);
            $info->setMode(AnovaInfo::MODE_COOK);
            $client->setInfo($info);//that's it,
            for($i=0;$i<5;$i++){//chill for 5 seconds to let everything go.
                echo ".";
                sleep(1);
            }
            echo "\n\n";
            echo $client->getInfo();
            echo"\n✌️\n";
        }
        catch(Exception $e){
            echo "Error: ".$e->getMessage();
        }
    }
}
(new Demo())->run();
