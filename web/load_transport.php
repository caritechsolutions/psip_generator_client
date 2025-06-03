<?php
if (isset($_GET['transport'])) {
    $transportName = $_GET['transport'];
    $configFilePath = "transports/$transportName/config.ini";

    if (file_exists($configFilePath)) {
        $config = parse_ini_file($configFilePath, true);

// Convert boolean values to strings
        if (isset($config['global'])) {
            $config['global']['TVCT_current'] = $config['global']['TVCT_current'] ? 'true' : 'false';
        }


 // Convert boolean values to strings for the 'access_controlled', 'hidden', and 'hide_guide' fields
        foreach ($config as $key => $value) {
            if (is_array($value) && isset($value['access_controlled'])) {
                $config[$key]['access_controlled'] = $value['access_controlled'] ? 'true' : 'false';
            }
            if (is_array($value) && isset($value['hidden'])) {
                $config[$key]['hidden'] = $value['hidden'] ? 'true' : 'false';
            }
            if (is_array($value) && isset($value['hide_guide'])) {
                $config[$key]['hide_guide'] = $value['hide_guide'] ? 'true' : 'false';
            }
        }

        if (isset($config['MGT'])) {
            $config['MGT']['tables'] = [];
            foreach ($config['MGT'] as $key => $value) {
                if (strpos($key, 'table_') === 0) {
                    list($type, $PID, $version_number, $number_bytes) = explode(',', $value);
                    $config['MGT']['tables'][] = [
                        'type' => $type,
                        'PID' => $PID,
                        'version_number' => $version_number,
                        'number_bytes' => $number_bytes
                    ];
                    unset($config['MGT'][$key]);
                }
            }
        }


if (isset($config['STT'])) {
            $config['STT']['DS_status'] = $config['STT']['DS_status'] ? 'true' : 'false';
        }


        echo json_encode($config);
    } else {
        echo json_encode(['error' => 'Config file not found']);
    }
} else {
    echo json_encode(['error' => 'Transport not specified']);
}
?>