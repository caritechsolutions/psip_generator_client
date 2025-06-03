<?php
if (isset($argv[1])) {
    $ts_name = $argv[1];
    $version = $argv[2];

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    // Database connection
    $dbHost = '10.8.0.20';
    $dbName = 'cariepg';
    $dbUsername = 'newroot2';
    $dbPassword = 'Password!10';

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

$globaleventid = 0;

// fectch epg data from database

function fetchXmltvData($pdo, $xmltv_id, $startTime) {

 $events = [];
    // Calculate the end time as $startTime + 3 hours
    $endTime = $startTime + 3 * 3600; // 3 hours in seconds

    // First query to get the service_id and service_name from the services table
    $stmt = $pdo->prepare("
        SELECT service_id, service_name 
        FROM services 
        WHERE xmltv_id = ?
    ");
    $stmt->execute([$xmltv_id]);

    // Fetch the result
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if service is found
    if (!$service) {

         $events = generateDummyEvents($startTime, $endTime);
        return $events; // Return empty array if no service is found for the given xmltv_id
    }

    $service_id = $service['service_id'];

    // Second query to get events from the events table based on service_id and time window
    $stmt = $pdo->prepare("
        SELECT * 
        FROM events 
        WHERE service_id = ? AND (
            (start_time <= ? AND ADDTIME(start_time, duration) > ?) OR
            (start_time >= ? AND start_time < ?)
        )
    ");
    // Execute the query with the appropriate parameters
    $stmt->execute([
        $service_id, 
        date('YmdHis', $startTime), 
        date('YmdHis', $startTime), 
        date('YmdHis', $startTime), 
        date('YmdHis', $endTime)
    ]);

   
    // Fetching all events into an array
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = $row;
    }

// If no events found, generate dummy events
    if (empty($events)) {
        $events = generateDummyEvents($startTime, $endTime);
    }

    return $events;
}


// Function to generate dummy events
function generateDummyEvents($startTime, $endTime) {
    $dummyEvents = [];
    $currentTime = $startTime;
    
    
    // Generate dummy events from startTime to endTime in 1-hour intervals
    while ($currentTime < $endTime) {
        $endDummyTime = $currentTime + 1800; // 1 hour in seconds

        $dummyEvents[] = [
            'start_time' => date('YmdHis', $currentTime),
            'duration' => '00:30:00', // 1 hour
            'event_name' => 'Local Programming',
            'event_id' => '0x' . str_pad(dechex($GLOBALS['globaleventid']), 4, "0", STR_PAD_LEFT),
            'event_desc' => 'Local Programming',
        ];
        $GLOBALS['globaleventid']++;
        $currentTime = $endDummyTime;
    }

    
    return $dummyEvents;
}



// read from the config file for the selected transport.

function readConfigFile($transportName) {
    $configFilePath = "/var/www/html/epg/transports/{$transportName}/config.ini";
    $configData = parse_ini_file($configFilePath, true);

    $channels = [];
    $xmlurlchannelname = [];

    foreach ($configData as $key => $value) {
        if (strpos($key, 'channel') === 0) {
            $channels[$value['source_id']] = [
    'short_name' => $value['short_name'], // Change 'name' to 'short_name'
    'xmltv_settings' => $value['xmltv_settings'], // Replace 'another_var' with the correct key
    'xmltv_id' => $value['xmltv_id'],
];
             
        }
    }

    return $channels;
}


function convertHexToDecimal($hex) {
    return hexdec($hex);
}


function generateAlignedStartTime($offsetHours = 0) {
    $currentUtcTime = time();
    $currentUtcTime -= ($currentUtcTime % 10800);  // Align to the nearest 3-hour block
    return $currentUtcTime + ($offsetHours * 3600);
}


// function to generate ETM ID

function generateETMId($sourceId, $eventId) {

 
    $sourceIdHex = $sourceId;

    

    // Convert eventId to binary and ensure it is a 14-bit number
    $eventIdBinary = str_pad(decbin(hexdec($eventId)), 14, '0', STR_PAD_LEFT);



    // Drop the two MSB and append '10' to the right (LSB side)
    $modifiedEventIdBinary = substr($eventIdBinary, 0) . '10';



    // Convert the modified binary back to a decimal number
    $modifiedEventId = dechex(bindec($modifiedEventIdBinary));



    // Convert the modified eventId back to a hexadecimal format, ensuring it is 4 digits
    // $modifiedEventIdHex = sprintf('%04X', $modifiedEventId);

$modifiedEventIdHex = str_pad($modifiedEventId, 4, '0', STR_PAD_LEFT);
 
//  echo $modifiedEventIdHex . "<br>";

    // Combine sourceId and modified eventId to get the final ETM_id
    $etmId = $sourceIdHex . $modifiedEventIdHex;

    return $etmId;
}



// function to generate the xml for the ETT files


function generateEttEntry2($channelName, $eventId, $desc, $sourceId, $pid, $version) {
// echo $desc . "here<br>";
$ett_pid = "0000";
   switch ($pid) {
  case "7424":
    $ett_pid = "7680";
    break;
  case "7425":
    $ett_pid = "7681";
    break;
  case "7426":
    $ett_pid = "7682";
    break;
  case "7427":
    $ett_pid = "7683";
    break;
  case "7428":
    $ett_pid = "7684";
    break;
  case "7429":
    $ett_pid = "7685";
    break;
  case "7430":
    $ett_pid = "7686";
    break;
  case "7431":
    $ett_pid = "7687";
    break;

} 
   $ettXml = '
  <ETT version="' . $version . '" protocol_version="0" ETT_table_id_extension="' . $eventId . '" ETM_id="' . generateETMId($sourceId, $eventId) . '">
    <metadata PID="' . $ett_pid . '"/>
    <extended_text_message>
      <string language="eng" text="' . $desc . '"/>
    </extended_text_message>
  </ETT>';

    return $ettXml;
}



// function to generate the xml for the EIT files


function generateATSC_EIT_XML($sourceId, $channelName, $pdo, $xmltv_id, $pid, $startTime, $version) {
    
$eitXmlData = "<ATSC_EIT version=\"$version\" source_id=\"$sourceId\" protocol_version=\"0\">
    <metadata PID=\"$pid\"/>";
    
$ettXmlData = ''; // Initialize ETT XML data here

    

// then we call the database for what we are looking for


// Fetch the events
$events = fetchXmltvData($pdo, $xmltv_id, $startTime);



// Loop through the events and use the data
foreach ($events as $event) {
   
 // Convert start to timestamp
    $start = strtotime($event['start_time']);

    // Convert MySQL TIME type duration to seconds
    $duration = $event['duration']; // Duration is in the format HH:MM:SS
    list($hours, $minutes, $seconds) = explode(':', $duration);
    $durationInSeconds = $hours * 3600 + $minutes * 60 + $seconds;

    // Calculate end time
    // $end = $start + $durationInSeconds;

     

    $title = $event['event_name'] ?? 'No Title';
    $description = $event['event_desc'] ?? 'No Description';

     if ($title == "Local Programming")
            {

              $title = $channelName . " Programing";
              $description = $channelName . " Programing";
            }

    $title = htmlspecialchars((string) $title, ENT_COMPAT, 'UTF-8');
    $description = htmlspecialchars((string) $description, ENT_COMPAT, 'UTF-8');

    // Use the data as needed
   // echo "Event Title: $title\n";
   // echo "Starts at: " . date('Y-m-d H:i:s', $start) . "\n";
   // echo "Ends at: " . date('Y-m-d H:i:s', $end) . "\n";
   // echo "Duration: $duration\n";
   // echo "Description: $description\n";
   // echo "-------------------------\n";

    // You can perform any other operations with the event data here



           $eitXmlData .= "
    <event event_id=\"" . $event['event_id'] . "\" start_time=\"" . gmdate('Y-m-d H:i:s', $start) . "\" ETM_location=\"0x01\" length_in_seconds=\"$durationInSeconds\">
      <title_text>
        <string language=\"eng\" text=\"$title\"/>
      </title_text>
    </event>";

$ettXmlContent = generateEttEntry2($channelName, $event['event_id'], $description, $sourceId, $pid, $version);
$ettXmlData .= $ettXmlContent;


}






$eitXmlData .= "
  </ATSC_EIT>";


// echo $eitXmlData . "\n";


    return array('eitXmlData' => $eitXmlData, 'ettXmlData' => $ettXmlData);
}


















// Where the work begins

$transportName = $ts_name;

$channels = readConfigFile($transportName);


// do some definations here


$pids = ["0x1d00", "0x1d01", "0x1d02", "0x1d03", "0x1d04", "0x1d05", "0x1d06", "0x1d07"];

$offsetHours = 0;



// now we begin to loop to generate 8 EIT and ETT files

for ($i = 0; $i < 8; $i++) {
    $startTime = generateAlignedStartTime($offsetHours);  // we get the start time based on the atsc rules

// we load the headers in the arrays for both EIT and ETT

    $eitXmlData = '<?xml version="1.0" encoding="UTF-8?><tsduck>'; 
    $ettXmlData = '<?xml version="1.0" encoding="UTF-8?><tsduck>';

// we grab out first pid and convert it to decimal for use in file generation
    $pid = convertHexToDecimal($pids[$i]);

  	//	echo $pid . "\n";

		// now we start the loop which will work on each channel in the lineup
 		
			foreach ($channels as $sourceId => $channelInfo) {
			
    				$channelName = $channelInfo['short_name']; // Use 'name' to access the short_name
   				 $xmltv_id = $channelInfo['xmltv_id'];
                             //    echo gmdate('Y-m-d H:i:s', $startTime) . "\n";
		             //      echo $channelName . "\n";
			     // 	echo $xmltv_id . "\n";

                                      $xmlData = generateATSC_EIT_XML($sourceId, $channelName, $pdo, $xmltv_id, $pid, $startTime, $version);
                                       
                                       $eitXmlData .= $xmlData['eitXmlData'];
                                       $ettXmlData .= $xmlData['ettXmlData'];
                                       

					}

$eitXmlData .= '</tsduck>';
    $ettXmlData .= '</tsduck>';

    $eitOutputFilePath = "/var/www/html/epg/transports/{$transportName}/{$transportName}_eit{$i}.xml";
    $ettOutputFilePath = "/var/www/html/epg/transports/{$transportName}/{$transportName}_ett{$i}.xml";

    file_put_contents($eitOutputFilePath, $eitXmlData);
    file_put_contents($ettOutputFilePath, $ettXmlData);

$offsetHours += 3;

				}










}
?>