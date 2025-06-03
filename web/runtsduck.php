<?php

if (isset($argv[1])) {
    $ts_name = $argv[1];
    
    $version = $argv[2];

function formatFileSize($bytes) {
    return number_format($bytes);
}

$eit0_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit0.xml"));
$eit1_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit1.xml"));
$eit2_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit2.xml"));
$eit3_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit3.xml"));
$eit4_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit4.xml"));
$eit5_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit5.xml"));
$eit6_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit6.xml"));
$eit7_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit7.xml"));

$ett0_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett0.xml"));
$ett1_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett1.xml"));
$ett2_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett2.xml"));
$ett3_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett3.xml"));
$ett4_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett4.xml"));
$ett5_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett5.xml"));
$ett6_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett6.xml"));
$ett7_size = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett7.xml"));



error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define GPS leap seconds
   function getleaps() {
      $leaps = array(46828800, 78364801, 109900802, 173059203, 252028804, 315187205, 346723206, 393984007, 425520008, 457056009, 504489610, 551750411, 599184012, 820108813, 914803214, 1025136015, 1119744016, 1167264017);
      return $leaps;
   }

// Test to see if a GPS second is a leap second
   function isleap($gpsTime) {
      $isLeap = FALSE;
      $leaps = getleaps();
      $lenLeaps = count($leaps);
      for ($i = 0; $i < $lenLeaps; $i++) {
         if ($gpsTime == $leaps[$i]) {
            $isLeap = TRUE;
         }
      }
      return $isLeap;
   }

// Count number of leap seconds that have passed
   function countleaps($gpsTime, $dirFlag){
      $leaps = getleaps();
      $lenLeaps = count($leaps);
      $nleaps = 0;  // number of leap seconds prior to gpsTime
      for ($i = 0; $i < $lenLeaps; $i++) {
         if (!strcmp('unix2gps', $dirFlag)) {
            if ($gpsTime >= $leaps[$i] - $i) {
               $nleaps++;
            }
         } elseif (!strcmp('gps2unix', $dirFlag)) {
            if ($gpsTime >= $leaps[$i]) {
               $nleaps++;
            }
         } else {
            echo "ERROR Invalid Flag!";
         }
      }
      return $nleaps;
   }

// Convert Unix Time to GPS Time
   function unix2gps($unixTime){
      // Add offset in seconds
      if (fmod($unixTime, 1) != 0) {
         $unixTime = $unixTime - 0.5;
         $isLeap = 1;
      } else {
         $isLeap = 0;
      }
      $gpsTime = $unixTime - 315964800;
      $nleaps = countleaps($gpsTime, 'unix2gps');
      $gpsTime = $gpsTime + $nleaps + $isLeap;
      return $gpsTime;
   }

// Convert GPS Time to Unix Time
   function gps2unix($gpsTime){
     // Add offset in seconds
     $unixTime = $gpsTime + 315964800;
     $nleaps = countleaps($gpsTime, 'gps2unix');
     $unixTime = $unixTime - $nleaps;
     if (isleap($gpsTime)) {
        $unixTime = $unixTime + 0.5;
     }
     return $unixTime;
   }



function generateSTTXml($filename, $version) {
 
    $transportName = $filename;

$currentUnixTime = time();

// Load config file
$config = parse_ini_file('/var/www/html/epg/transports/' . $transportName . '/config.ini', true);

// Create a new XML document
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tsduck></tsduck>');


// Add TVCT element with attributes
$tvct = $xml->addChild('TVCT');
$tvct->addAttribute ('version', $version);

if (isset($config['global']['TVCT_current'])) {
    if ($config['global']['TVCT_current'] === '') {
        $tvct->addAttribute('current', 'false');
    } else if ($config['global']['TVCT_current'] === '1') {
        $tvct->addAttribute('current', 'true');
    } else {
        // Default to true if the value is not recognized
        $tvct->addAttribute('current', 'true');
    }
} else {
    // Default to true if TVCT_current is not set
    $tvct->addAttribute('current', 'true');
}


$tvct->addAttribute('transport_stream_id', $config['global']['transport_stream_id']);
$tvct->addAttribute('protocol_version', $config['global']['protocol_version']);

// Add metadata
$metadata = $tvct->addChild('metadata');
$metadata->addAttribute('PID', "8,187");

// Add channels
foreach ($config as $key => $channel) {
    if (strpos($key, 'channel') === 0) {
        $ch = $tvct->addChild('channel');
        $ch->addAttribute('short_name', $channel['short_name']);
        $ch->addAttribute('major_channel_number', $channel['major_channel_number']);
        $ch->addAttribute('minor_channel_number', $channel['minor_channel_number']);
        $ch->addAttribute('modulation_mode', $channel['modulation_mode']);
        $ch->addAttribute('carrier_frequency', $channel['carrier_frequency']);
        $ch->addAttribute('channel_TSID', $channel['channel_TSID']);
        $ch->addAttribute('program_number', $channel['program_number']);
        $ch->addAttribute('ETM_location', $channel['ETM_location']);






        



if (isset($channel['access_controlled'])) {
    if ($channel['access_controlled'] === '') {
        $ch->addAttribute('access_controlled', 'false');
    } else if ($channel['access_controlled'] === '1') {
        $ch->addAttribute('access_controlled', 'true');
    } else {
        // Default to true if the value is not recognized
        $ch->addAttribute('access_controlled', 'true');
    }
} else {
    // Default to true if TVCT_current is not set
    $ch->addAttribute('access_controlled', 'true');
}


if (isset($channel['hidden'])) {
    if ($channel['hidden'] === '') {
        $ch->addAttribute('hidden', 'false');
    } else if ($channel['hidden'] === '1') {
        $ch->addAttribute('hidden', 'true');
    } else {
        // Default to true if the value is not recognized
        $ch->addAttribute('hiddent', 'true');
    }
} else {
    // Default to true if TVCT_current is not set
    $ch->addAttribute('hidden', 'true');
}


if (isset($channel['hide_guide'])) {
    if ($channel['hide_guide'] === '') {
        $ch->addAttribute('hide_guide', 'false');
    } else if ($channel['hide_guide'] === '1') {
        $ch->addAttribute('hide_guide', 'true');
    } else {
        // Default to true if the value is not recognized
        $ch->addAttribute('hide_guide', 'true');
    }
} else {
    // Default to true if TVCT_current is not set
    $ch->addAttribute('hide_guide', 'true');
}





        $ch->addAttribute('service_type', $channel['service_type']);
        $ch->addAttribute('source_id', $channel['source_id']);
      
        $sld = $ch->addChild('service_location_descriptor');
        $sld->addAttribute('PCR_PID', $channel['PCR_PID']);
        
        for ($i = 1; $i <= 2; $i++) {
            if (isset($channel["component_stream_type$i"]) && isset($channel["elementary_PID$i"])) {
                $component = $sld->addChild('component');
                $component->addAttribute('stream_type', $channel["component_stream_type$i"]);
                $component->addAttribute('elementary_PID', $channel["elementary_PID$i"]);
            }
        }
    }
}

// Add MGT element
$mgt = $xml->addChild('MGT');
$mgt->addAttribute('version', $version);
$mgt->addAttribute('protocol_version', $config['MGT']['protocol_version']);
$metadata_mgt = $mgt->addChild('metadata');
$metadata_mgt->addAttribute('PID', "8,187");

// Add MGT tables
foreach ($config['MGT'] as $key => $value) {
    if (strpos($key, 'table_') === 0) {
        list($type, $PID, $version_number, $number_bytes) = explode(',', $value);
        $table = $mgt->addChild('table');
        $table->addAttribute('type', $type);
        $table->addAttribute('PID', trim($PID));
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', trim($number_bytes));
    }


}

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT');
        $table->addAttribute('PID', '0x1E80');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', '182');


$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-0');
        $table->addAttribute('PID', '0x1D00');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit0_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-1');
        $table->addAttribute('PID', '0x1D01');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit1_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-2');
        $table->addAttribute('PID', '0x1D02');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit2_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-3');
        $table->addAttribute('PID', '0x1D03');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit3_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-4');
        $table->addAttribute('PID', '0x1D04');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit4_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-5');
        $table->addAttribute('PID', '0x1D05');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit5_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-6');
        $table->addAttribute('PID', '0x1D06');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit6_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'EIT-7');
        $table->addAttribute('PID', '0x1D07');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['eit7_size']);



$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-0');
        $table->addAttribute('PID', '0x1E00');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett0_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-1');
        $table->addAttribute('PID', '0x1E01');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett1_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-2');
        $table->addAttribute('PID', '0x1E02');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett2_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-3');
        $table->addAttribute('PID', '0x1E03');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett3_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-4');
        $table->addAttribute('PID', '0x1E04');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett4_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-5');
        $table->addAttribute('PID', '0x1E05');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett5_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-6');
        $table->addAttribute('PID', '0x1E06');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett6_size']);

$table = $mgt->addChild('table');
        $table->addAttribute('type', 'ETT-7');
        $table->addAttribute('PID', '0x1E07');
        $table->addAttribute('version_number', $version);
        $table->addAttribute('number_bytes', $GLOBALS['ett7_size']);


// Add STT element
$stt = $xml->addChild('STT');
$stt->addAttribute('protocol_version', $config['STT']['protocol_version']);
$stt->addAttribute('system_time', unix2gps($currentUnixTime));
$stt->addAttribute('GPS_UTC_offset', $config['STT']['GPS_UTC_offset']);



if (isset($config['STT']['DS_status'])) {
    if ($config['STT']['DS_status'] === '') {
        $stt->addAttribute('DS_status', 'false');
    } else if ($config['STT']['DS_status'] === '1') {
        $stt->addAttribute('DS_status', 'true');
    } else {
        // Default to true if the value is not recognized
        $stt->addAttribute('DS_status', 'true');
    }
} else {
    // Default to true if TVCT_current is not set
    $stt->addAttribute('DS_status', 'true');
}



$metadata_stt = $stt->addChild('metadata');
$metadata_stt->addAttribute('PID', "8,187");

// Convert XML to formatted string
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
$xmlString = $dom->saveXML();

// Print the XML to the screen
// echo "<pre>" . htmlentities($xmlString) . "</pre>";


$unixTime = gps2unix(unix2gps($currentUnixTime)); // Unix Time you want to convert

// Convert Unix Time to DateTime
$date = date('Y-m-d H:i:s', $unixTime);


// Output the DateTime in a specific format
echo $date . "\r";




// Save the XML to a file
$xmlFilePath = '/var/www/html/epg/transports/' . $transportName . '/' . $transportName . '_stt.xml';
$dom->save($xmlFilePath);

}

function startTSDuck($ts_name2) {

$transportName = $ts_name2;


// Load config file
$config = parse_ini_file('/var/www/html/epg/transports/' . $transportName . '/config.ini', true);


//set ip and port

$udpaddress = $config['OUTPUT']['udpaddress'];
$ctl_port = $config['OUTPUT']['control_port'];

// echo $udpaddress;

     $command = "/usr/bin/sudo -i nohup /root/tsduck/bin/release-x86_64-crane2/tsp -vvvvvv --control-local 127.0.0.1 --control-port " . $ctl_port . " --bitrate 1000000 -I null -P regulate ";
    
    $command .= "-P inject --bitrate 300000 -p 8187 /var/www/html/epg/transports/$transportName/" . $transportName . "_stt.xml=100 -P regulate ";

    $command .= "-P inject --bitrate 300000 -p 7424 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit0.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7425 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit1.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7426 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit2.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7427 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit3.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7428 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit4.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7429 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit5.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7430 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit6.xml=100 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7431 /var/www/html/epg/transports/$transportName/" . $transportName . "_eit7.xml=100 -P regulate ";


    $command .= "-P inject --bitrate 300000 -p 7680 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett0.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7681 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett1.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7682 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett2.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7683 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett3.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7684 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett4.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7685 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett5.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7686 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett6.xml=1000 -P regulate ";
    $command .= "-P inject --bitrate 300000 -p 7687 /var/www/html/epg/transports/$transportName/" . $transportName . "_ett7.xml=1000 -P regulate ";

  

    
    
    

    $command .= "-O ip $udpaddress";
    
echo $command;

exec($command . " > /dev/null 2>/dev/null & echo $!");  // Run command in the background


}

function restartPlugin($pluginIndex, $ts_name) {

$transportName = $ts_name;

$config = parse_ini_file('/var/www/html/epg/transports/' . $transportName . '/config.ini', true);

$ctl_port = $config['OUTPUT']['control_port'];

    $command = "/usr/bin/sudo -i nohup /root/tsduck/bin/release-x86_64-crane2/tspcontrol --tsp 127.0.0.1:" . $ctl_port . " restart -s $pluginIndex";
//echo $command;
    exec($command . " > /dev/null 2>/dev/null & echo $!");
}

// Path to the STT XML file
$sttXmlPath = $ts_name;

// Start TSDuck initially
 startTSDuck($ts_name);



function isCommandExecutionTime($lastExecutionHour) {

  $currentHour = date('G'); // Get the current hour in 24-hour format without leading zeros

    // Hours where the command should be executed
    $executionHours = [0, 3, 6, 9, 12, 15, 18, 21];

    // Check if current hour is one of the execution hours and hasn't run this hour
    if (in_array($currentHour, $executionHours) && $currentHour != $lastExecutionHour) {
        return true;
    }
    
    return false;
}


function logExecution($message) {
    $logFile = 'execution_log.txt';

    // Get the current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Create the log message
    $logMessage = "[$timestamp] $message" . PHP_EOL;

    // Append the log message to the log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}



$lastExecutionHour = -1; // Initialize the last execution hour to an invalid hour

function handleVersionFile($ts) {
    $filename = '/var/www/html/epg/transports/' . $ts . '/version.txt';
// echo $filename;
    // Check if the file exists
    if (!file_exists($filename)) {
        // Create the file and write zero to it
        file_put_contents($filename, '0');
    }

    // Read the current version from the file
    $version = (int)file_get_contents($filename);

    // Echo out the current version for debugging purposes (optional)
     echo "Current version: $version\n";

    // Increment the version, ensuring it wraps around after 31 (i.e., version 32 should reset to 0)
    $newVersion = ($version + 1) % 32;

    // Write the new version back to the file
    file_put_contents($filename, $newVersion);

    return $version;
}


// Infinite loop to update the STT file and restart the plugin every 5 seconds
while (true) {

    // Generate a new STT XML file with the current time
    generateSTTXml($sttXmlPath, $version);

    // Restart the specific inject plugin which handles the STT table (index needs to match the command)
     // Adjust the index if necessary to match your inject plugin for STT
     restartPlugin(2, $ts_name); 

    // Wait for 5 seconds before updating again
    sleep(5);
  // usleep(500000);

				// Check if it's time to run the 12-hour commands
   				 if (isCommandExecutionTime($lastExecutionHour)) {
        
        
       					 $version = handleVersionFile($ts_name);
       					 // echo "Loaded version: $version\n";


   				 $command = "/usr/bin/sudo /usr/bin/nohup /usr/bin/php /var/www/html/epg/epg_gen.php $ts_name $version >/dev/null 2>&1 & echo $!";
   				 exec($command);
             
// Log the command execution
        logExecution($ts_name . " EPG Updated with version " . $version);                     
sleep(5);

$GLOBALS['eit0_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit0.xml"));
$GLOBALS['eit1_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit1.xml"));
$GLOBALS['eit2_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit2.xml"));
$GLOBALS['eit3_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit3.xml"));
$GLOBALS['eit4_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit4.xml"));
$GLOBALS['eit5_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit5.xml"));
$GLOBALS['eit6_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit6.xml"));
$GLOBALS['eit7_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_eit7.xml"));

$GLOBALS['ett0_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett0.xml"));
$GLOBALS['ett1_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett1.xml"));
$GLOBALS['ett2_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett2.xml"));
$GLOBALS['ett3_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett3.xml"));
$GLOBALS['ett4_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett4.xml"));
$GLOBALS['ett5_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett5.xml"));
$GLOBALS['ett6_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett6.xml"));
$GLOBALS['ett7_size'] = formatFileSize(filesize("/var/www/html/epg/transports/" . $ts_name . "/" . $ts_name . "_ett7.xml"));


foreach ([4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36] as $pluginIndex) {


            restartPlugin($pluginIndex, $ts_name); 
          sleep(1);
        // logExecution($ts_name . " restarted Plugin " . $pluginIndex . " for " . $version);
        }


        logExecution($ts_name . " restarted Plugins for " . $version);
        

        // Update the last execution hour to prevent repeated runs within the same hour
        $lastExecutionHour = date('G');
    }



}


// end of if set 
}
?>