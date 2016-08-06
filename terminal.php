<?php

/*
 * Calling URL looks like:
 * "http://192.168.1.36?user=(username,opt)&pswd=(password,opt)&serverID=(server id,opt)&termID=(macaddress,req)&termdata=(data,req)"
 *
 */

function clean_data($cxn, $string)
{
	$string = strip_tags($string);
	return mysqli_real_escape_string($cxn, $string);
}

function do_error($cxn, $dodie,$errmsg)
{
	global $inUser;
    global $inPassword;
    global $inTermID;
    global $inServID;
    
	$query = "INSERT INTO messages (user, password, termid, serverid, data) VALUES "
    ."(\"" . $inUser . "\",\"" . $inPassword . "\",\"" . $inTermID . "\",\"" . $inServID . "\",\"" . $errmsg . "\");";
    $result = mysqli_query($cxn, $query) or die("wd*error:Could not load messages table");
    if ($dodie==1){
    	die();
    }
    return;
}
 
// Load vars and connect to DB
//include("../protected/varstermrouter.inc");  // Use something like this if you have moved the INC file to a protected folder

// Load vars and connect to DB
//include("varstermrouter.inc");
include("../../protected/varstermrouter2.inc");
//$cxn = mysqli_connect($host, $user, $password);
//mysqli_select_db($database);
$database = "termrouter";
$cxn = mysqli_connect($host, $user, $password,$database );
mysqli_select_db ($cxn, $database);


//1a. Clean incoming data in case this is a hacking attempt

$inUser = clean_data($cxn, $_REQUEST['user']);
$inPassword = clean_data($cxn, $_REQUEST['pswd']);
$inTermID = clean_data($cxn, $_REQUEST['termID']);
$inServID = clean_data($cxn, $_REQUEST['serverID']);
$inData = clean_data($cxn, $_REQUEST['termdata']);

if (isset($_REQUEST['PID'])) {
	$pktStr = clean_data($cxn, $_REQUEST['PID']); // always 3 digits string echos progID sent with prompt data
} else {
	$pktStr = "";
}	

if (isset($_REQUEST['CKS'])) {
	$chkStr = clean_data($cxn, $_REQUEST['CKS']); // always 3 digits
} else {
	$chkStr = "";
}	


//1b. if SignIn, delete all references to this terminal in the database
if ($inData == chr(15))
{
	$query = "DELETE FROM fromterminal  WHERE user=\"" . $inUser . "\" and termid=\"" . $inTermID . "\" and serverid=\"" . $inServID . "\"";
	$result = mysqli_query($cxn, $query) or do_error($cxn, 1,"wd*error:Couldn't execute query 1b delete fromterminal");// die("wd*error:Couldn't execute query 1b delete fromterminal");
	if ($result){
        do_error($cxn, 0,"wd*warning:deleted FromTerminal records on SignIn");
		//die("wd*status:deleted FromTerminal records");
	}
	$query = "DELETE FROM fromserver  WHERE user=\"" . $inUser . "\" and termid=\"" . $inTermID . "\" and serverid=\"" . $inServID . "\"";
	$result = mysqli_query($cxn, $query) or do_error($cxn, 1,"wd*error:Couldn't execute query 1b delete fromserver"); //die("wd*error:Couldn't execute query 1b delete fromserver");
	if ($result){
		do_error($cxn, 0,"wd*warning:deleted FromServer records on SignIn");
		//die("wd*status:deleted FromServer records");
	}
}

$num = 0;

//1c. if any data from this terminal already exists in FromTerminal, return error message.
if ($inData != "")
{
	$query = "SELECT * FROM fromterminal  WHERE user=\"" . $inUser . "\" and termid=\"" . $inTermID . "\" and serverid=\"" . $inServID . "\";";
	$result = mysqli_query($cxn, $query) or die("wd*error:Couldn't execute query 1c search");
	if ($result){
		$num = mysqli_num_rows($result);
	} else {
		$num=0;
	}

	if ($num > 0) {
        do_error($cxn, 0,"wd*warning:Sequence Error, data already present from this terminal");
		//die("wd*error:Sequence Error, data already present from this terminal");
	}
}


// 2. Load the FromTerminal table for the application server to check
if ($num == 0) {
    $query = "INSERT INTO fromterminal (user, password, termid, serverid, pid, chksum, data) VALUES "
    ."(\"" . $inUser . "\",\"" . $inPassword . "\",\"" . $inTermID . "\",\"" . $inServID . "\",\"" . $pktStr . "\",\"" . $chkStr . "\",\"" . $inData . "\");";
    $result = mysqli_query($cxn, $query) or do_error($cxn, 1,"wd*error:Couldn't add data to FromTerminal table");// die("wd*error:Couldn't execute query 1");
}


// 3. See if there is anything back from from the app server. Wait until there is something...
$num = 0;
$sql = "SELECT data,pid FROM fromserver  WHERE user = \"" . $inUser .
    									"\" and password = \"" . $inPassword . 
    									"\" and serverID = \"" . $inServID . 
    									"\" and termID = \"" . $inTermID . "\";";


// 4. Keep trying until server answer is found. Keeps connection open until request resolves
while ($num == 0) {
    $result = mysqli_query($cxn, $sql) or do_error($cxn, 1,"wd*error:Couldn't read from FromServer table");// die("wd*error:Couldn't execute query 2");
    if ($result > 0) {
        $num = mysqli_num_rows($result);
    }
}



if ($num > 0) {
    // Return server's reply to Terminal and delete from transfer table
    $row = mysqli_fetch_assoc($result);
        
    if ($row['pid'] != '') {
        echo "wd*d" . $row['pid'] .":" . $row['data'];
    } else {
        echo "wd*data:" . $row['data'];
    }
        
    $sql = "DELETE FROM fromserver  WHERE user = \"" . $inUser .
						"\" and password = \"" . $inPassword . 
						"\" and serverID = \"" . $inServID . 
						"\" and termID = \"" . $inTermID . "\";";
    $result = mysqli_query($cxn, $sql) or do_error($cxn, 1,"wd*error:Couldn't delete data from FromServer table");// die("wd*error:Couldn't execute query 3");
    
} elseif ($num == 0) {
    do_error($cxn, 0,"wd*warning:Timed out waiting for data from Application Server");// echo "wd*error:Timed Out";
}


/**/
// 5. Load log information (optional)
$query = "INSERT INTO terminallog (user, password, termid, serverid, pid, data) VALUES (\"" . $inUser . "\",\"" . $inPassword . "\",\"" . $inTermID . "\",\"" . $inServID . "\",\"" . $pktStr . "\",\"" . $inData . "\");";
$result = mysqli_query($cxn, $query) or do_error($cxn, 1,"wd*error:Couldn't add Terminal Log Entry");// die("wd*error:Couldn't execute terminal log entry");
/**/

?>
