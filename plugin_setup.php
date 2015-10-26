<?php
//$DEBUG=true;

include_once "/opt/fpp/www/common.php";
include_once "functions.inc.php";

//include the array of sports
include_once "SPORTS.inc.php";

$pluginName = "SportsTicker";
$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";


$logFile = $settings['logDirectory']."/".$pluginName.".log";


if(isset($_POST['submit']))
{
	$SPORTS =  implode(',', $_POST["SPORTS"]);

//	echo "Writring config fie <br/> \n";
	
	WriteSettingToFile("SPORTS",$SPORTS,$pluginName);
	WriteSettingToFile("ENABLED",urlencode($_POST["ENABLED"]),$pluginName);
	WriteSettingToFile("SEPARATOR",urlencode($_POST["SEPARATOR"]),$pluginName);
	WriteSettingToFile("LAST_READ",urlencode($_POST["LAST_READ"]),$pluginName);
}

	
	
	$SPORTS = urldecode(ReadSettingFromFile("SPORTS",$pluginName));
	
	$ENABLED = urldecode(ReadSettingFromFile("ENABLED",$pluginName));
	$SEPARATOR = urldecode(ReadSettingFromFile("SEPARATOR",$pluginName));
	$LAST_READ = urldecode(ReadSettingFromFile("LAST_READ",$pluginName));
	if($SEPARATOR == "") {
		$SEPARATOR="|";
	}
	//echo "sports read: ".$SPORTS."<br/> \n";
	

	if((int)$LAST_READ == 0 || $LAST_READ == "") {
		$LAST_READ=0;
	}
?>

<html>
<head>
</head>

<div id="<?echo $pluginName;?>" class="settings">
<fieldset>
<legend><?php echo $pluginName;?> Support Instructions</legend>

<p>Known Issues:
<ul>
<li>NONE</li>
</ul>

<p>Configuration:
<ul>
<li>Select the sports you want to include in the list, shift click to multi select</li>
<li>Enable the plugin, Restart FPPD</li>
<li>Configure your separator that will appear between scores.. Default |
</ul>
<ul>
<li>Add the crontabAdd options to your crontab to have the SportsTicker get data every X minutes to process commands</li>
<li>NOTE: This plugin utilizes the MessageQueue plugin. Please install that plugin before configuring this plugin</li>
</ul>



<form method="post" action="http://<? echo $_SERVER['SERVER_NAME']?>/plugin.php?plugin=<?echo $pluginName;?>&page=plugin_setup.php">


<?
echo "<input type=\"hidden\" name=\"LAST_READ\" value=\"".$LAST_READ."\"> \n";

$restart=0;
$reboot=0;

echo "ENABLE PLUGIN: ";

if($ENABLED== 1 || $ENABLED == "on") {
		echo "<input type=\"checkbox\" checked name=\"ENABLED\"> \n";
//PrintSettingCheckbox("Radio Station", "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");
	} else {
		echo "<input type=\"checkbox\"  name=\"ENABLED\"> \n";
}

echo "<p/> \n";

echo "Sports: ";
printSportsOptions();


echo "<p/> \n";


echo "<p/> \n";

echo "Separator: \n";

echo "<input type=\"text\" name=\"SEPARATOR\" size=\"3\" value=\"".$SEPARATOR."\"> \n";

?>
<p/>
<input id="submit_button" name="submit" type="submit" class="buttons" value="Save Config">


<?
 if(file_exists($pluginUpdateFile))
 {
 	//echo "updating plugin included";
	include $pluginUpdateFile;
}

?>
</form>
<p>To report a bug, please file it against the sms Control plugin project on Git: https://github.com/LightsOnHudson/FPP-Plugin-SportsTicker

</fieldset>
</div>
<br />
</html>
