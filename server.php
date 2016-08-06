<?php

/*
 * Application Server polls Connection Host (server.php) with a periodic (1000ms) "Ping"
 * Polling is done with an HTTP query that looks like:
 *
 * "http://192.168.1.36?user=(username)&pswd=(password)&serverID=(serverid)&termID=GET"
 * (all parameters above are required for a polling query)
 *
 *
 * Reply to a poll from an Application Server will be as follows:
 *
 * 1.
 * If there is Terminal Data (either control code such as SignIn, or collected data), the reply
 * to the Application Server will look like:
 *
  wd*user:(user)
  wd*password:(password)
  wd*termid:(terminal ID)
  wd*serverid:(server ID)
  wd*cksm:(checksum)
  wd*pkid:(packet ID)
  wd*data:(data)
  wd*COMPLETE
 *
 * 2.
 * If there is no data from any terminal for the Application Server, it will look like:
 *
  wd*Timed Out
 *
 * 3.
 * If there is an error, the reply will look like
 *
 *          wd*ERROR:(error message)
 *
 *
 * If reply to poll contains terminal data or control code, (there is terminal data being routed to
 * Application Server), the Application Server is expected to reply to the poll reply with an
 * HTTP GET query that looks like:
 *
 * "http://192.168.1.36?user=(username)&pswd=(password)&serverID=(serverid)&termID=(termid)&servdata=(data,req)"
 *
 * This query contains the identifying information and the next prompt command for the terminal specified
 * in the "wd:termid" listed above.
 *
 *
 * No reply to application server until next poll.
 *
 *
 * sample GET HTTP header:
  GET /index.html?userid=joe&password=guessme HTTP/1.1
  Host: www.mysite.com
  User-Agent: Mozilla/4.0

 * sample POST HTTP header:
  POST /login.jsp HTTP/1.1
  Host: www.mysite.com
  User-Agent: Mozilla/4.0
  Content-Length: 27
  Content-Type: application/x-www-form-urlencoded

  userid=joe&password=guessme

 * sample server response:
  HTTP/1.1 200 OK
  Date: Fri, 31 Dec 1999 23:59:59 GMT
  Content-Type: text/plain
  Content-Length: 42
  some-footer: some-value
  another-footer: another-value

  abcdefghijklmnopqrstuvwxyz1234567890abcdef

 */

function clean_data($cxn, $string) {
    return mysqli_real_escape_string($cxn, $string);
}

// Load vars and connect to DB
//include("varstermrouter.inc");
// Load vars and connect to DB
//include("varstermrouter.inc");
include("../../protected/varstermrouter2.inc");
//$cxn = mysqli_connect($host, $user, $password);
//mysqli_select_db($database);
$database = "termrouter";
$cxn = mysqli_connect($host, $user, $password, $database);
mysqli_select_db($cxn, $database);


$DO_LOG = 1;

// 1. Clean incoming data in case this is a hacking attempt
$inUser = clean_data($cxn, $_REQUEST['user']);
$inPassword = clean_data($cxn, $_REQUEST['pswd']);
$inTermID = clean_data($cxn, $_REQUEST['termID']);
$inServID = clean_data($cxn, $_REQUEST['serverID']);
$inData = clean_data($cxn, $_REQUEST['servdata']);

if (isset($_REQUEST['pid'])) {
//	$inPID = clean_data($cxn, $_REQUEST['pid']); // always 3 digits string echos progID sent with prompt data
	$inPID = $_REQUEST['pid']; // always 3 digits string echos progID sent with prompt data
} else {
	$inPID = "";
}	

if ($inUser == "")
    die("wd*ERROR: No USER specified");
if ($inServID == "")
    die("wd*ERROR: No Server ID specified");

// 2a. Check for conflicting Application Server IDs already attached
if (isset($_REQUEST['checkstart'])) {
    $chkStart = clean_data($cxn, $_REQUEST['checkstart']);

    if ($chkStart == 1) { // Begin startup, register entry to see if server w/ matching IDs already running
//** home server check
        if ($inUser == "demo")
            die("check started");
        if ($inUser == "demoWDS")
            $inUser = "demo";

        $query = "INSERT INTO serverinit (user, serverid) VALUES (\"" . $inUser . "\",\"" . $inServID . "\");";
        $result = mysqli_query($cxn, $query) or die("Couldn't execute check 1");
        die("check started");
    }

    if ($chkStart == 2) { // Finish startup, if registration entry still exists, OK to start up
//** home server check
        if ($inUser == "demo")
            die("check denied");
        if ($inUser == "demoWDS")
            $inUser = "demo";
//      
        $query = "SELECT COUNT(*) AS ResponseCount FROM serverinit WHERE user=\"" . $inUser . "\"  and serverid=\"" . $inServID . "\";";
        $result = mysqli_query($cxn, $query) or die("Couldn't execute check 2 search");

        $row = mysqli_fetch_array($result);

        if ($row['ResponseCount'] > 0) {
            $query_delete = "DELETE FROM serverinit  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
            $result_delete = mysqli_query($cxn, $query_delete) or die("Couldn't execute check 2 delete");

            // if any prompt data from this server already exists in FromServer table, delete it.
            // ... server side
            $query = "SELECT * FROM fromserver  WHERE user=\"" . $inUser . "\"  and serverid=\"" . $inServID . "\";";
            $result = mysqli_query($cxn, $query) or die("Couldn't execute check 2b search");

            if ($result) {
                $query_delete = "DELETE FROM fromserver  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
                $result_delete = mysqli_query($cxn, $query_delete) or die("Couldn't execute check 2b delete");
            }

            // ... terminal side
            $query = "SELECT * FROM fromterminal  WHERE user=\"" . $inUser . "\"  and serverid=\"" . $inServID . "\";";
            $result = mysqli_query($cxn, $query) or die("Couldn't execute check 2c search");

            if ($result) {
                $query_delete = "DELETE FROM fromterminal  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
                $result_delete = mysqli_query($cxn, $query_delete) or die("Couldn't execute check 2c delete");
            }

            die("check ok");
        } else
            die("check denied");
    }
}

if ($inPassword == "")
    die("wd*ERROR: No PASSWORD specified");
if ($inTermID == "")
    die("wd*ERROR: No Terminal ID specified");


//** home server check
if ($inUser == "demoWDS")
    $inUser = "demo";
//      
// 2b. if any startup request exists for matching server ID codes, delete it (deny conflicting server startup)
$query = "SELECT * FROM serverinit  WHERE user=\"" . $inUser . "\"  and serverid=\"" . $inServID . "\";";
$result = mysqli_query($cxn, $query) or die("Couldn't execute query 1b search");

if ($result) {
    $query_delete = "DELETE FROM serverinit  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
    $result_delete = mysqli_query($cxn, $query_delete) or die("Couldn't execute query 1b delete");
}


// 3. if any prompt data for this terminal already exists in FromServer table, delete it.
//      if this is a GET, then inData = ""
if ($inData != "") {
    $query = "SELECT * FROM fromserver  WHERE user=\"" . $inUser . "\" and termid=\"" . $inTermID . "\" and serverid=\"" . $inServID . "\";";
    $result = mysqli_query($cxn, $query) or die("Couldn't execute query 1c search");

    if ($result) {
        $query_delete = "DELETE FROM fromserver  WHERE user=\"" . $inUser . "\" and termid=\"" . $inTermID . "\" and serverid=\"" . $inServID . "\";";
        $result_delete = mysqli_query($cxn, $query_delete) or die("Couldn't execute query 1c delete");
    }
}


// 4. Pass in new prompt data for terminal, if any, and load to FromServer for terminal.php to see
//      if this is a GET, then inData = ""
if ($inData != "") {
    $query = "INSERT INTO fromserver (user, password, termid, serverid, pid, data) VALUES (\"" . $inUser .
            "\",\"" . $inPassword . "\",\"" . $inTermID .
            "\",\"" . $inServID . "\",\"" . $inPID . "\",\"" . $inData . "\");";
    $result = mysqli_query($cxn, $query) or die("Couldn't execute query 1");

    //Load log information (optional)
    if ($DO_LOG > 0) {
        $query = "INSERT INTO serverlog (user, password, termid, serverid, pid, appserver_ip, data) VALUES "
        ."(\"" . $inUser . "\",\"" . $inPassword . "\",\"" . $inTermID . "\",\"" . $inServID . "\",\"" . $inPID . "\",\"" . $_SERVER['REMOTE_ADDR'] . "\",\"" . $inData . "\");";
        $result = mysqli_query($cxn, $query) or die("wd*error:Couldn't execute server log entry");
    }
    die("data posted");
}


// 5. Look for terminal input to process and return to application server
$sentTO = false;
$sql = "SELECT * FROM fromterminal  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
$result = mysqli_query($cxn, $sql); // don't die here just because table might be empty!
if ($result) {
    $num = mysqli_num_rows($result);
} else {
    $num = 0;
}
if ($num > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "wd*user:" . $row['user'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*password:" . $row['password'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*termid:" . $row['termid'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*serverid:" . $row['serverid'];
        echo chr(13) . chr(10); //"<br>";
        if ($row['pid'] != '') {
            echo "wd*pkid:" . $row['pid'];
            echo chr(13) . chr(10); //"<br>";
        }
        if ($row['chksum'] != '') {
            echo "wd*cksm:" . $row['chksum'];
            echo chr(13) . chr(10); //"<br>";
        }
        echo "wd*data:" . urlencode($row['data']);
        echo chr(13) . chr(10); //"<br>";
        echo "wd*COMPLETE";
        echo chr(13) . chr(10); //"<br>";
    }
    $sql = "DELETE FROM fromterminal  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
    $result = mysqli_query($cxn, $sql) or die("wd*ERROR: cannot delete incoming terminal data from table" . chr(13) . chr(10));
} elseif ($num == 0) {
    echo "wd*Timed Out" . chr(13) . chr(10);
    $sentTO = true;
}



// 6. Look for any error messages to pass back to application server
$sql = "SELECT * FROM messages  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
$result = mysqli_query($cxn, $sql); // don't die here just because table might be empty!
if ($result) {
    $num = mysqli_num_rows($result);
} else {
    $num = 0;
}
if ($num > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "wd*user:" . $row['user'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*password:" . $row['password'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*termid:" . $row['termid'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*serverid:" . $row['serverid'];
        echo chr(13) . chr(10); //"<br>";
        echo "wd*data:" . urlencode($row['data']);
        echo chr(13) . chr(10); //"<br>";
        echo "wd*COMPLETE";
        echo chr(13) . chr(10); //"<br>";
    }
    $sql = "DELETE FROM messages  WHERE user=\"" . $inUser . "\" and serverid=\"" . $inServID . "\";";
    $result = mysqli_query($cxn, $sql) or die("wd*ERROR: cannot delete incoming message data from table" . chr(13) . chr(10));
} elseif ($num == 0) {
    if ($sentTO == false) // not already sent...
        echo "wd*Timed Out" . chr(13) . chr(10);
}


// 7. Load polling log information (optional)
if ( ($DO_LOG > 0) and ($inTermID != 'GET') ) {
//if ( ($DO_LOG > 0) ) {
    $query = "INSERT INTO serverlog (user, password, termid, serverid, pid, data) VALUES "
    ."(\"" . $inUser . "\",\"" . $inPassword . "\",\"" . $inTermID . "\",\"" . $inServID . "\",\"" . $inPID . "\",\"" . $inData . "\");";
    $result = mysqli_query($cxn, $query) or die("wd*error:Couldn't execute server log entry");
}
?>
