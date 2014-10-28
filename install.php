<?php
/*
MySQL-Limits for ISPConfig
Copyright (c) 2014, Alexandre Alouit <alexandre.alouit@gmail.com>
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

$backup_dir = "/var/backup/";
$backup_file = date("Ymdhi")."mysql-limits";
$listing = array(
0 => array(
"source" => "./interface/web/sites/templates/database_user_edit.htm", 
"destination" => "/usr/local/ispconfig/interface/web/sites/templates/database_user_edit.htm", 
"owners" => "ispconfig:ispconfig", "permissions" => "750"),
1 => array(
"source" => "./interface/web/sites/form/database_user.tform.php", 
"destination" => "/usr/local/ispconfig/interface/web/sites/form/database_user.tform.php", 
"owners" => "ispconfig:ispconfig", "permissions" => "750"),
2 => array(
"source" => "./interface/web/server/plugins-available/mysql_clientdb_plugin.inc.php", 
"destination" => "/usr/local/ispconfig/server/plugins-available/mysql_clientdb_plugin.inc.php", 
"owners" => "root:root", "permissions" => "770"),
3 => array(
"source" => "./interface/web/sites/lib/lang/*_database_user.lng", 
"destination" => "/usr/local/ispconfig/interface/web/sites/lib/lang/", 
"owners" => "ispconfig:ispconfig", "permissions" => ""),
);

if(!file_exists("/usr/local/ispconfig/server/lib/config.inc.php") OR !file_exists("/usr/local/ispconfig/server/lib/mysql_clientdb.conf")) {
	print("Unable to load the ISPConfig defaut configuration files.\n");
	exit;
}
require_once "/usr/local/ispconfig/server/lib/config.inc.php";
require_once "/usr/local/ispconfig/server/lib/mysql_clientdb.conf";
if(!isset($conf['app_version']) OR $conf['app_version'] != '3.0.5.4p4') {
	print("This version is unsupported.\n");
	exit;
}

if(!file_exists($backup_dir)) {
	print("Backup directory not found.\n");
	print("Create it, and relaunch me!\n");
	exit;
}

print("Create backup on " . $backup_dir . " directory\n");
$filelist = "";

foreach($listing as $key => $value) {
	$filelist = $filelist . " " . $value["destination"];
}

exec ("/bin/tar czvf " . $backup_dir . $backup_file . ".tar.gz " $filelist);

if(!file_exists($backup_dir . $backup_file )) {
	print("There is a problem with the backup.\n");
	exit;
}
print("Backup finished\n");

print("Start copying file..\n");


foreach($listing as $key => $value) {
	print($key . " -> " $value);
	exec("cp -Rp " . $value["source"] . " " . $value["destination"]);
	exec("chown -R " . $value["owners"] . " " . $value["destination"]);
	exec("chmod -R " . $value["permissions"] . " " . $value["destination"]);
}

if (!$buffer = mysql_connect($clientdb_host, $clientdb_user, $clientdb_password)) {
	print("There is a problem with the MySQL connection.\n");
	exit
}

mysql_db_query($conf['db_database'], "ALTER TABLE `web_database_user` ADD `max_user_connections` bigint(20) NOT NULL DEFAULT '-1';", $buffer);
mysql_db_query($conf['db_database'], "ALTER TABLE `web_database_user` ADD `max_queries_per_hour` bigint(20) NOT NULL DEFAULT '-1';", $buffer);
mysql_db_query($conf['db_database'], "ALTER TABLE `web_database_user` ADD `max_updates_per_hour` bigint(20) NOT NULL DEFAULT '-1';", $buffer);
mysql_db_query($conf['db_database'], "ALTER TABLE `web_database_user` ADD `max_connections_per_hour` bigint(20) NOT NULL DEFAULT '-1';", $buffer);

print("Done my job. Enjoy!\n");
exit
?>
