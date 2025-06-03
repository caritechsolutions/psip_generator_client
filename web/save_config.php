<?php
// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['transportName'], $data['configData'])) {
    $transportName = $data['transportName'];
    $configData = $data['configData'];

    $config = [];

    // Add global section
    $config['global'] = $configData['global'];

    // Add channel sections
    $channelIndex = 1;
    foreach ($configData['channels'] as $channelName => $channelData) {
        $config["channel$channelIndex"] = array_merge($channelData);
        $channelIndex++;
    }

    // Add MGT section
    $config['MGT'] = [
        'version' => $configData['MGT']['version'],
        'protocol_version' => $configData['MGT']['protocol_version']
    ];
    foreach ($configData['MGT']['tables'] as $index => $table) {
        $config['MGT']["table_$index"] = "{$table['type']}, {$table['PID']}, {$table['version_number']}, {$table['number_bytes']}";
    }

    // Add STT section
    $config['STT'] = $configData['STT'];

// Add output section
    $config['OUTPUT'] = $configData['OUTPUT'];


    // Convert array to INI string
    function arrayToIniString($array) {
        $output = '';
        foreach ($array as $section => $values) {
            $output .= "[$section]\n";
            foreach ($values as $key => $value) {
                $output .= "$key = $value\n";
            }
            $output .= "\n";
        }
        return $output;
    }

    // Create directory if not exists
    $directory = "transports/$transportName";
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    // Save the config file
    $iniContent = arrayToIniString($config);
    file_put_contents("$directory/config.ini", $iniContent);





// Load config file
$config = parse_ini_file('/var/www/html/transports/' . $transportName . '/config.ini', true);

// Create a new XML document
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tsduck></tsduck>');


// Add TVCT element with attributes
$tvct = $xml->addChild('TVCT');
$tvct->addAttribute ('version', $config['global']['TVCT_version']);

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
$mgt->addAttribute('version', $config['MGT']['version']);
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
        $table->addAttribute('version_number', trim($version_number));
        $table->addAttribute('number_bytes', trim($number_bytes));
    }
}


// Convert XML to formatted string
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
$xmlString = $dom->saveXML();

// Print the XML to the screen
echo "<pre>" . htmlentities($xmlString) . "</pre>";

// Save the XML to a file
$xmlFilePath = '/var/www/html/epg/transports/' . $transportName . '/ts3_tvct.xml';
$dom->save($xmlFilePath);


//stt generation of separate file

// Load config file
$config = parse_ini_file('/var/www/html/epg/transports/' . $transportName . '/config.ini', true);

// Create a new XML document
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tsduck></tsduck>');





// Add STT element
$stt = $xml->addChild('STT');
$stt->addAttribute('protocol_version', $config['STT']['protocol_version']);
$stt->addAttribute('system_time', $config['STT']['system_time']);
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
echo "<pre>" . htmlentities($xmlString) . "</pre>";

// Save the XML to a file
$xmlFilePath = '/var/www/html/epg/transports/' . $transportName . '/ts3_stt.xml';
$dom->save($xmlFilePath);



    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
}
?>