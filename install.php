<?php
/*
 * The Connection Host is composed of three parts:
 *
 *  1. The "server.php" script which is the web page that the Application Server uses.
 *  2. The "terminal.php" script which is the web page that the 7802 Terminals use.
 *  3. A set of tables ("fromterminal", "fromserver", "serverinit", and "messages") 
 *     stored in the MySQL database of your choice.
 *
 * The database tables are only used for transient storage and will never accumulate a significant
 * quantity of data in normal use.
 *
 *  The Connection Host is installed by:
 *     -Copy the included PHP and INC files to a folder on your web hosting server (use your usual
 *      FTP client for this). These can be copied to the main folder of your website, or to a subfolder.
 *
 *     -Edit the "vars" file.
 *      Notice that "install.php" and "server.php" and "terminal.php" all use the file "varstermrouter.inc".
 *      This file needs to be edited to contain the correct connection and password data for your database.
 *      You may want to put it in a restricted access folder as a security measure (don't forget to change
 *      the "includes" in the other files if your do).
 *
 *     -Build the necessary MySQL database tables.
 *      Either run this installer by pointing your web browser to
 *      http://www.myWebsite/myConnectionHostFolder/install.php
 *      or use the included DDL (database definition log) file to build the tables manually using the
 *      software tools provided by your web hosting provider.
 */

/*
 * This file needs to be edited and variables need to be set for access to your MySQL database server
 */
include("varstermrouter.inc");




/*
 Some hosting services will not allow us to drop (delete) or create databases here and you will
 have to remove these lines from the script and create the database manually using your hosting
 service tools (cpanel, etc).

 If the database does not already exist and you would like the installer to try to create it,
 use the following lines. Just remove the // at
 the beginning of the following 2 lines.
 */

//echo "DROP DATABASE IF EXISTS $database;";
//echo "CREATE DATABASE $database;";


$link = mysql_connect($host, $user, $password);
mysql_select_db($database);

//
// This removes the old tables if they exist
//
// Some hosting services will not allow us to drop (delete) tables here and you will have to remove
// these lines from the script and drop the tables manually using your hosting service tools (cpanel, etc).
$query = "DROP TABLE IF EXISTS `fromserver`;";
$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute delete FromServer");
$query = "DROP TABLE IF EXISTS `fromterminal`;";
$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute delete FromTerminal");
$query = "DROP TABLE IF EXISTS `messages`;";
$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute delete Messages");
$query = "DROP TABLE IF EXISTS `serverinit`;";
$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute delete ServerInit");
$query = "DROP TABLE IF EXISTS `terminallog`;";
$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute delete TerminalLog");
$query = "DROP TABLE IF EXISTS `serverlog`;";
$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute delete ServerLog");


$query = "CREATE TABLE IF NOT EXISTS `serverinit` ("
."  `user` varchar(50) NOT NULL,"
."  `serverid` varchar(50) NOT NULL,"
."  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
."  KEY `user` (`user`)"
.") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute create ServerInit");

// This creates the tables in the database
$query = "CREATE TABLE IF NOT EXISTS `fromterminal` ("
."  `user` varchar(50) NOT NULL,"
."  `password` varchar(255) NOT NULL,"
."  `serverid` varchar(50) NOT NULL,"
."  `termid` varchar(50) NOT NULL,"
."  `pid` varchar(3) DEFAULT NULL,"
."  `chksum` varchar(3) DEFAULT NULL,"
."  `data` varchar(10000) DEFAULT NULL,"
."  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
."  KEY `user` (`user`),"
."  KEY `termid` (`termid`),"
."  KEY `serverid` (`serverid`),"
."  KEY `password` (`password`)"
.") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute create FromTerminal");

$query = "CREATE TABLE IF NOT EXISTS `fromserver` ("
."  `user` varchar(50) NOT NULL,"
."  `password` varchar(255) NOT NULL,"
."  `serverid` varchar(50) NOT NULL,"
."  `termid` varchar(50) NOT NULL,"
."  `pid` varchar(3) DEFAULT NULL,"
."  `data` varchar(64000) DEFAULT NULL,"
."  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
."  KEY `user` (`user`),"
."  KEY `termid` (`termid`),"
."  KEY `serverid` (`serverid`),"
."  KEY `password` (`password`)"
.") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute create FromServer");

// Optional log tables
$query = "CREATE TABLE IF NOT EXISTS `terminallog` ("
."  `user` varchar(50) NOT NULL,"
."  `password` varchar(255) NOT NULL,"
."  `serverid` varchar(50) NOT NULL,"
."  `termid` varchar(50) NOT NULL,"
."  `pid` varchar(3) NOT NULL,"
."  `data` varchar(2000) DEFAULT NULL,"
."  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
."  KEY `user` (`user`)"
.") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute create TerminalLog");

$query = "CREATE TABLE IF NOT EXISTS `serverlog` ("
."  `user` varchar(50) NOT NULL,"
."  `password` varchar(255) NOT NULL,"
."  `serverid` varchar(50) NOT NULL,"
."  `termid` varchar(50) NOT NULL,"
."  `pid` varchar(3) NOT NULL,"
."  `data` varchar(1000) DEFAULT NULL,"
."  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
."  `appserver_ip` varchar(20) NOT NULL,"
."  KEY `user` (`user`)"
.") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute create ServerLog");

$query = "CREATE TABLE IF NOT EXISTS `messages` ("
."  `user` varchar(50) NOT NULL,"
."  `password` varchar(255) NOT NULL,"
."  `serverid` varchar(50) NOT NULL,"
."  `termid` varchar(50) NOT NULL,"
."  `data` varchar(64000) DEFAULT NULL,"
."  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
."  KEY `user` (`user`),"
."  KEY `termid` (`termid`),"
."  KEY `serverid` (`serverid`),"
."  KEY `password` (`password`)"
.") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_clienttable = mysql_query($query) or die ("ERROR:Could not execute create Messages");


echo "Installation successful!";