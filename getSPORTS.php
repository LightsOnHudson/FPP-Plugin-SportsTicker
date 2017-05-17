#!/usr/bin/php
<?
error_reporting(0);

$pluginName ="SportsTicker";
$myPid = getmypid();

$messageQueue_Plugin = "MessageQueue";
$MESSAGE_QUEUE_PLUGIN_ENABLED=false;
$SportsTickerPluginVersion = "2.0";

$DEBUG=false;

//$Plugin_DBName = "/tmp/FPP.".$pluginName.".db";

$skipJSsettings = 1;
require_once("/opt/fpp/www/config.php");
require_once("/opt/fpp/www/common.php");
//include_once("/opt/fpp/www/plugin.php");

include_once("functions.inc.php");
include_once "SPORTS.inc.php";
require ("lock.helper.php");

define('LOCK_DIR', '/tmp/');
define('LOCK_SUFFIX', $pluginName.'.lock');



$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);


//print_r($pluginSettings);


$logFile = $settings['logDirectory']."/".$pluginName.".log";

$messageQueuePluginPath = $pluginDirectory."/".$messageQueue_Plugin."/";

$messageQueueFile = urldecode(ReadSettingFromFile("MESSAGE_FILE",$messageQueue_Plugin));

if(($pid = lockHelper::lock()) === FALSE) {
	exit(0);

}

if(file_exists($messageQueuePluginPath."functions.inc.php"))
	{
		include $messageQueuePluginPath."functions.inc.php";
		$MESSAGE_QUEUE_PLUGIN_ENABLED=true;

	} else {
		logEntry("Message Queue Plugin not installed, some features will be disabled");
	}	


//if($MESSAGE_QUEUE_PLUGIN_ENABLED) {
//	$queueMessages = getNewPluginMessages("SMS");
//	print_r($queueMessages);
//} else {
//	logEntry("MessageQueue plugin is not enabled/installed");
//}	

//	print_r($pluginSettings);
//	echo "\n";
	
	//$SPORTS = urldecode(ReadSettingFromFile("SPORTS",$pluginName));
	$SPORTS = urldecode($pluginSettings['SPORTS']);
	
	//$ENABLED = urldecode(ReadSettingFromFile("ENABLED",$pluginName));
	$ENABLED = urldecode($pluginSettings['ENABLED']);
	
	//$SEPARATOR = urldecode(ReadSettingFromFile("SEPARATOR",$pluginName));
	$SEPARATOR = urldecode($pluginSettings['SEPARATOR']);
	
	//$LAST_READ = urldecode(ReadSettingFromFile("LAST_READ",$pluginName));
	$LAST_READ = $pluginSettings['LAST_READ'];

	$DEBUG = urldecode($pluginSettings['DEBUG']);
	//echo "enabled: ".$ENABLED."\n";
	
	if($DEBUG) {
		echo "Debug is on \n";
	}
	
//echo "ENABLED: ".$ENABLED."\n";
if($ENABLED != "ON") {
	logEntry("Plugin Status: DISABLED Please enable in Plugin Setup to use & Restart FPPD Daemon");
	lockHelper::unlock();
	exit(0);
	
}

$MESSAGE_FILE = urldecode($pluginSettings['MESSAGE_FILE']);

if(trim($MESSAGE_FILE) == "") {
	$MESSAGE_FILE = "/home/fpp/media/config/FPP.".$pluginName.".db";
}


// set up DB connection
$MESSAGE_FILE= $settings['configDirectory']."/FPP.".$pluginName.".db";

//echo "PLUGIN DB:NAME: ".$Plugin_DBName;

$db = new SQLite3($MESSAGE_FILE) or die('Unable to open database');

//create the tables if not exist
createTables();

//$SEPARATOR = urldecode(ReadSettingFromFile("SEPARATOR",$pluginName));

$SPORTS_READ = explode(",",$SPORTS);

if($DEBUG) {
	echo "Incoming sports reading: \n";
	print_r($SPORTS_READ);
	print_r($SPORTS_DATA_ARRAY);
}
$messageText="";



for($i=0;$i<=count($SPORTS_READ)-1;$i++) {
	if($DEBUG)
		echo "Retrieving data for: ".$SPORTS_READ[$i]."\n";
	
	foreach($SPORTS_DATA_ARRAY as $sport) {
		
		if($DEBUG) {
			echo "Sport: ".print_r($sportLink)."\n";
			echo "sport incomining from array: ".$sport[0]."\n";
		}
		//echo $SPORTS_READ[$i]. " is in Sports data array\n";
			
		//fetch the information
		//echo "sprots data array: ".$SPORTS_DATA_ARRAY[$i][1]."\n";
		if($sport[0] == $SPORTS_READ[$i]) {
			if($DEBUG) {
				print_r($sport);
				echo "Getting scores from: ".$sport[1]."\n";
			}
			$sportsScores = file_get_contents($sport[1]);
		
			//echo $sportsScores;

			$sportData= json_decode($sportsScores, TRUE);
		} else {
			if($DEBUG)
			echo "Not a match \n";
			
			continue;
		}
		
	
		if($DEBUG) {
			echo "Game try array: ".$sportData['data']['games']['game'][0]['location']."\n";
			$DAY_GAME_COUNT = count($sportData['data']['games']['game']);
			echo "Day game count: ".$DAY_GAME_COUNT."\n";
		}
		
		foreach($sportData['data']['games']['game'] as $game) {
			//print_r($game);
			if($DEBUG) {
				echo "Home Code: ".$game['home_code']." Team Name: ".$game['home_team_name']."   Away Code: ".$game['away_code']."  Away Team name: ".$game['away_team_name']."\n";
			    echo "Game Time: ".$game['time']."\n";
			}
		    $homeScore = 0;
		    $awayScore = 0;
		    //get the inning score and add up the totals
		    foreach($game['linescore']['inning'] as $inning) {
		    	
		    	$homeScore += $inning['home'];
		    	$awayScore += $inning['away'];
		    }
		    
		    if($DEBUG) {
			    echo "Home Score: ".$homeScore."\n";
			    echo "Away Score: ".$awayScore."\n";
		    }
		    
		    $table = "messages";
		    $messageText = $game['home_team_name']." ".$homeScore." ".$game['away_team_name']." ".$awayScore;
		    //insertMessage($Plugin_DBName, $table, $messageText, $pluginName, $pluginData=$SPORTS_READ[$i]);
		    addNewMessage($messageText,$pluginName,$pluginData=$SPORTS_READ[$i], $MESSAGE_FILE);
		    $messageText="";
		    $messageLine="";
		}
		
		foreach($sportData['data']['games']['game'] as $game) {
			if($DEBUG)
			print_r($game);
			//echo "Home Code: ".$game['home_code']."   Away Code: ".$game['away_code']."\n";
		}
		if($DEBUG) {
			print_r($sportData);
		}
	
	

	//addNewMessage($messageLine,$pluginName,$pluginData=$SPORTS_READ[$i]);
	
	
	}
	
}

function search_in_array($value, $arr){

	$num = 0;
	for ($i = 0; $i < count($arr); ) {
		if($arr[$i][0] == $value) {
			$num++;
		}
		$i++;
	}
	return $num ;
}
lockHelper::unlock();
?>
