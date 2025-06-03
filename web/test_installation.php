<?php
echo "EPG PSIP Client Installation Test\n";
echo "=================================\n\n";

// Test PHP
echo "PHP Version: " . PHP_VERSION . "\n";

// Test PHP extensions
$required_extensions = ['mysqli', 'curl', 'xml', 'mbstring'];
foreach ($required_extensions as $ext) {
    echo "$ext Extension: " . (extension_loaded($ext) ? "OK" : "MISSING") . "\n";
}

// Test write permissions
$test_file = __DIR__ . "/transports/test.txt";
if (@file_put_contents($test_file, "test") !== false) {
    echo "Write Permissions: OK\n";
    @unlink($test_file);
} else {
    echo "Write Permissions: FAILED\n";
}

// Test sudo
$output = [];
exec("sudo whoami 2>&1", $output, $return);
echo "Sudo Access: " . ($return === 0 ? "OK (running as " . implode('', $output) . ")" : "FAILED") . "\n";

// Test TSDuck
$output = [];
exec("which tsp 2>&1", $output, $return);
echo "TSDuck in PATH: " . ($return === 0 ? "OK" : "NOT IN PATH") . "\n";

// Check actual TSDuck binary location
$tsduck_paths = glob("/root/tsduck/bin/*/tsp");
if (!empty($tsduck_paths)) {
    echo "TSDuck found at: " . $tsduck_paths[0] . "\n";
    
    // Check if it's executable
    if (is_executable($tsduck_paths[0])) {
        echo "TSDuck executable: OK\n";
    } else {
        echo "TSDuck executable: NOT EXECUTABLE\n";
    }
} else {
    echo "TSDuck binary not found in expected location\n";
}

// Test database connection
if (file_exists(__DIR__ . '/epg_gen.php')) {
    // Extract database credentials from epg_gen.php
    $content = file_get_contents(__DIR__ . '/epg_gen.php');
    preg_match('/\$dbHost = \'([^\']+)\'/', $content, $host_match);
    preg_match('/\$dbName = \'([^\']+)\'/', $content, $name_match);
    preg_match('/\$dbUsername = \'([^\']+)\'/', $content, $user_match);
    preg_match('/\$dbPassword = \'([^\']+)\'/', $content, $pass_match);
    
    if ($host_match && $name_match && $user_match && $pass_match) {
        try {
            $pdo = new PDO("mysql:host={$host_match[1]};dbname={$name_match[1]};charset=utf8", 
                          $user_match[1], $pass_match[1]);
            echo "Database Connection: OK\n";
            
            // Test if tables exist
            $result = $pdo->query("SHOW TABLES LIKE 'services'");
            if ($result && $result->rowCount() > 0) {
                echo "Database Tables: OK\n";
            } else {
                echo "Database Tables: NOT FOUND\n";
            }
        } catch (Exception $e) {
            echo "Database Connection: FAILED - " . $e->getMessage() . "\n";
        }
    } else {
        echo "Database Configuration: NOT FOUND\n";
    }
} else {
    echo "epg_gen.php: NOT FOUND\n";
}

// Check for transports directory
if (is_dir(__DIR__ . '/transports')) {
    echo "Transports Directory: OK\n";
} else {
    echo "Transports Directory: NOT FOUND\n";
}

// Check web server user
echo "Running as user: " . exec('whoami') . "\n";

echo "\n=================================\n";
echo "Installation test complete.\n";
?>
