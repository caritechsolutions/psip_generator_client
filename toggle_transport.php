<?php
if (!isset($_GET['transport']) || !isset($_GET['action'])) {
    echo "Invalid parameters.";
    exit;
}

function handleVersionFile($ts) {
    $filename = '/var/www/html/epg/transports/' . $ts . '/version.txt';
echo $filename;
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


$transport = $_GET['transport'];
$action = $_GET['action'];


$statusFile = "/var/www/html/epg/transports/{$transport}/status.ini";



if (!file_exists($statusFile)) {
    echo "file not there, creating file";
  // Check if the file exists
    if (!file_exists($statusFile)) {
        // Create the file and write zero to it
       $filename = $statusFile;
echo "<br> file not there, creating file " . $filename ;

        file_put_contents($filename, '0');
    }


}

// Function to write to the status.ini file
function write_ini_file($file, $array, $i = 0) {
    $str = "";
    foreach ($array as $k => $v) {
        $str .= str_repeat(" ", $i * 2);
        if (is_array($v)) {
            $str .= "[$k]" . PHP_EOL;
            $str .= write_ini_file("", $v, $i + 1);
        } else {
            $str .= "$k=$v" . PHP_EOL;
        }
    }
    if ($file) {
        return file_put_contents($file, $str);
    } else {
        return $str;
    }
}

if ($action === 'start') {
    // Construct the command to start the transport

	// Example usage
$version = handleVersionFile($transport);
 echo "Loaded version: $version\n";


    $command = "/usr/bin/sudo /usr/bin/nohup /usr/bin/php /var/www/html/epg/epg_gen.php $transport $version >/dev/null 2>&1 & echo $!";
    exec($command);

 sleep(1);

    $command = "/usr/bin/sudo /usr/bin/nohup /usr/bin/php /var/www/html/epg/runtsduck.php $transport $version >/dev/null 2>&1 & echo $!";
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        echo "Command failed with code $return_var and output: " . implode("\n", $output);
        exit;
    }
    
    $pid = $output[0] ?? null;

    if (!$pid) {
        echo "Failed to retrieve PID. Command output: " . implode("\n", $output);
        exit;
    }

    // Read the existing status.ini
    $status = parse_ini_file($statusFile, true);
    if (!$status) {
        $status = [];
    }

    // Update with the new PID
    $status['Process']['pid'] = $pid;

    // Write back to the status.ini file
    if (write_ini_file($statusFile, $status)) {
        echo "Transport started successfully with PID $pid.";
    } else {
        echo "Failed to write status file.";
    }
} elseif ($action === 'stop') {
    // Read the status.ini
    $status = parse_ini_file($statusFile, true);
    if (!$status || !isset($status['Process']['pid'])) {
        echo "No running process found.";
        exit;
    }

    $pid = $status['Process']['pid'];

    // Construct the command to stop the transport
   // $command = "/usr/bin/sudo /usr/bin/nohup /usr/bin/kill $pid";

// echo $pid;

   
$command = '/usr/bin/sudo /usr/bin/ps -aux | /usr/bin/grep "' . $transport . '" | /usr/bin/awk \'{print $2}\'';

    exec($command, $output, $return_var);

// Print the output
foreach ($output as $line) {

  

$command = '/usr/bin/sudo /usr/bin/kill ' . $line;

echo $command . "<br>";
    exec($command, $output, $return_var);

    if ($return_var !== 0) {
        echo "Command failed with code $return_var and output: " . implode("\n", $output);
       // exit;
    }

}



$command = "/usr/bin/sudo /usr/bin/nohup /usr/bin/kill $pid";


   exec($command, $output, $return_var);

    if ($return_var !== 0) {
        echo "Command failed with code $return_var and output: " . implode("\n", $output);
       // exit;
    }

    

} else {
    echo "Invalid action.";
}
?>