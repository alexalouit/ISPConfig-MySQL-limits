<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class mysql_clientdb_plugin {

	var $plugin_name = 'mysql_clientdb_plugin';
	var $class_name  = 'mysql_clientdb_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['db'] == true) {
			return true;
		} else {
			return false;
		}

	}


	/*
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		//* Databases
		$app->plugins->registerEvent('database_insert', $this->plugin_name, 'db_insert');
		$app->plugins->registerEvent('database_update', $this->plugin_name, 'db_update');
		$app->plugins->registerEvent('database_delete', $this->plugin_name, 'db_delete');

		//* Database users
		$app->plugins->registerEvent('database_user_insert', $this->plugin_name, 'db_user_insert');
		$app->plugins->registerEvent('database_user_update', $this->plugin_name, 'db_user_update');
		$app->plugins->registerEvent('database_user_delete', $this->plugin_name, 'db_user_delete');


	}

	function process_host_list($action, $database_name, $database_user, $database_password, $host_list, $link, $database_rename_user = '', $user_read_only = false) {
		global $app;

		$action = strtoupper($action);

		// set to all hosts if none given
		if(trim($host_list) == '') $host_list = '%';

		// process arrays and comma separated strings
		if(!is_array($host_list)) $host_list = explode(',', $host_list);

		$success = true;
		if($action != 'RES') {
			if(!preg_match('/\*[A-F0-9]{40}$/', $database_password)) {
					$result = $link->query("SELECT PASSWORD('" . $link->escape_string($database_password) . "') as `crypted`");
					if($result) {
							$row = $result->fetch_assoc();
							$database_password = $row['crypted'];
							$result->free();
					}
			}
		}
		// loop through hostlist
		foreach($host_list as $db_host) {
			$db_host = trim($db_host);

			$app->log($action . ' for user ' . $database_user . ' at host ' . $db_host, LOGLEVEL_DEBUG);

			// check if entry is valid ip address
			$valid = true;
			if($db_host == '%' || $db_host == 'localhost') {
				$valid = true;
			} elseif(preg_match("/^[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}$/", $db_host)) {
				$groups = explode('.', $db_host);
				foreach($groups as $group){
					if($group<0 or $group>255)
						$valid=false;
				}
			} else {
				$valid = false;
			}

			if($valid == false) continue;

			if($action == 'GRANT') {
				if(!$link->query("GRANT " . ($user_read_only ? "SELECT" : "ALL") . " ON ".$link->escape_string($database_name).".* TO '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."';")) $success = false;
				$app->log("GRANT " . ($user_read_only ? "SELECT" : "ALL") . " ON ".$link->escape_string($database_name).".* TO '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."'; success? " . ($success ? 'yes' : 'no'), LOGLEVEL_DEBUG);
			} elseif($action == 'REVOKE') {
				if(!$link->query("REVOKE ALL PRIVILEGES ON ".$link->escape_string($database_name).".* FROM '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."';")) $success = false;
			} elseif($action == 'DROP') {
				if(!$link->query("DROP USER '".$link->escape_string($database_user)."'@'$db_host';")) $success = false;
			} elseif($action == 'RENAME') {
				if(!$link->query("RENAME USER '".$link->escape_string($database_user)."'@'$db_host' TO '".$link->escape_string($database_rename_user)."'@'$db_host'")) $success = false;
			} elseif($action == 'PASSWORD') {
				if(!$link->query("SET PASSWORD FOR '".$link->escape_string($database_user)."'@'$db_host' = '".$link->escape_string($database_password)."';")) $success = false;
			} elseif($action == 'RES') {
				if($database_password['max_user_connections'] == "-1" OR !isset($database_password['max_user_connections']) OR empty($database_password['max_user_connections'])) { $max_user_connections = 0; } else { $max_user_connections = $database_password['max_user_connections']; }
				if($database_password['max_queries_per_hour'] == "-1" OR !isset($database_password['max_queries_per_hour']) OR empty($database_password['max_queries_per_hour'])) { $max_queries_per_hour = 0; } else { $max_queries_per_hour = $database_password['max_queries_per_hour']; }
				if($database_password['max_updates_per_hour'] == "-1" OR !isset($database_password['max_updates_per_hour']) OR empty($database_password['max_updates_per_hour'])) { $max_updates_per_hour = 0; } else { $max_updates_per_hour = $database_password['max_updates_per_hour']; }
				if($database_password['max_connections_per_hour'] == "-1" OR !isset($database_password['max_connections_per_hour']) OR empty($database_password['max_connections_per_hour'])) { $max_connections_per_hour = 0; } else { $max_connections_per_hour = $database_password['max_connections_per_hour']; }
				if(!$link->query("GRANT USAGE ON ".$link->escape_string($database_name).".* TO '".$link->escape_string($database_user)."'@'$db_host' WITH MAX_USER_CONNECTIONS ".$link->escape_string($max_user_connections)." MAX_QUERIES_PER_HOUR ".$link->escape_string($max_queries_per_hour)." MAX_UPDATES_PER_HOUR ".$link->escape_string($max_updates_per_hour)." MAX_CONNECTIONS_PER_HOUR ".$link->escape_string($max_connections_per_hour).";")) $success = false;
				$app->log("GRANT USAGE ON ".$link->escape_string($database_name).".* TO '".$link->escape_string($database_user)."'@'$db_host' WITH MAX_USER_CONNECTIONS ".$max_user_connections." MAX_QUERIES_PER_HOUR ".$max_queries_per_hour." MAX_UPDATES_PER_HOUR ".$max_updates_per_hour." MAX_CONNECTIONS_PER_HOUR ".$max_connections_per_hour."; success? " . ($success ? 'yes' : 'no'), LOGLEVEL_DEBUG);
			}
		}

		return $success;
	}

	function drop_or_revoke_user($database_id, $user_id, $host_list){
		global $app;

		// set to all hosts if none given
		if(trim($host_list) == '') $host_list = '%';

		$db_user_databases = $app->db->queryAllRecords("SELECT * FROM web_database WHERE (database_user_id = ".$user_id." OR database_ro_user_id = ".$user_id.") AND active = 'y' AND database_id != ".$database_id);
		$db_user_host_list = array();
		if(is_array($db_user_databases) && !empty($db_user_databases)){
			foreach($db_user_databases as $db_user_database){
				if($db_user_database['remote_access'] == 'y'){
					if($db_user_database['remote_ips'] == ''){
						$db_user_host_list[] = '%';
					} else {
						$tmp_remote_ips = explode(',', $db_user_database['remote_ips']);
						if(is_array($tmp_remote_ips) && !empty($tmp_remote_ips)){
							foreach($tmp_remote_ips as $tmp_remote_ip){
								$tmp_remote_ip = trim($tmp_remote_ip);
								if($tmp_remote_ip != '') $db_user_host_list[] = $tmp_remote_ip;
							}
						}
						unset($tmp_remote_ips);
					}
				}
				$db_user_host_list[] = 'localhost';
			}
		}
		$host_list_arr = explode(',', $host_list);
		//print_r($host_list_arr);
		$drop_hosts = array_diff($host_list_arr, $db_user_host_list);
		//print_r($drop_hosts);
		$revoke_hosts = array_diff($host_list_arr, $drop_hosts);
		//print_r($revoke_hosts);

		$drop_host_list = implode(',', $drop_hosts);
		$revoke_host_list = implode(',', $revoke_hosts);
		//echo $drop_host_list."\n";
		//echo $revoke_host_list."\n";
		return array('revoke_hosts' => $revoke_host_list, 'drop_hosts' => $drop_host_list);
	}

	function db_insert($event_name, $data) {
		global $app, $conf;

		if($data['new']['type'] == 'mysql') {
			if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
				return;
			}

			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to mysql'.$link->connect_error, LOGLEVEL_ERROR);
				return;
			}

			// Charset for the new table
			if($data['new']['database_charset'] != '') {
				$query_charset_table = ' DEFAULT CHARACTER SET '.$data['new']['database_charset'];
			} else {
				$query_charset_table = '';
			}

			//* Create the new database
			if ($link->query('CREATE DATABASE '.$link->escape_string($data['new']['database_name']).$query_charset_table)) {
				$app->log('Created MySQL database: '.$data['new']['database_name'], LOGLEVEL_DEBUG);
			} else {
				$app->log('Unable to create the database: '.$link->error, LOGLEVEL_WARNING);
			}

			// Create the database user if database is active
			if($data['new']['active'] == 'y') {

				// get the users for this database
				$db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password`, `max_user_connections`, `max_queries_per_hour`, `max_updates_per_hour`, `max_connections_per_hour` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_user_id']) . "'");

				$db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_ro_user_id']) . "'");

				$host_list = '';
				if($data['new']['remote_access'] == 'y') {
					$host_list = $data['new']['remote_ips'];
					if($host_list == '') $host_list = '%';
				}
				if($host_list != '') $host_list .= ',';
				$host_list .= 'localhost';

				if($db_user) {
					if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);

				}
				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', true);
				}

				if($db_user && $db_user['database_user'] != 'root') {
					if($db_user['max_user_connections'] != "-1" OR $db_user['max_queries_per_hour'] != "-1" OR $db_user['max_updates_per_hour'] != "-1" OR $db_user['max_connections_per_hour'] != "-1") {
						// We use password like a door for data (it's nasty, but works)
						$this->process_host_list('RES', $data['new']['database_name'], $db_user['database_user'], array("max_user_connections" => $db_user['max_user_connections'], "max_queries_per_hour" => $db_user['max_queries_per_hour'], "max_updates_per_hour" => $db_user['max_updates_per_hour'], "max_connections_per_hour" => $db_user['max_connections_per_hour']), $host_list, $link);
					}
				}

			}

			$link->query('FLUSH PRIVILEGES;');
			$link->close();
		}
	}

	function db_update($event_name, $data) {
		global $app, $conf;

		// skip processing if database was and is inactive
		if($data['new']['active'] == 'n' && $data['old']['active'] == 'n') return;

		if($data['new']['type'] == 'mysql') {
			if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
				return;
			}

			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to the database: '.$link->connect_error, LOGLEVEL_ERROR);
				return;
			}

			// get the users for this database
			$db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_user_id']) . "'");
			$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['old']['database_user_id']) . "'");

			$db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_ro_user_id']) . "'");
			$old_db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['old']['database_ro_user_id']) . "'");

			$host_list = '';
			if($data['new']['remote_access'] == 'y') {
				$host_list = $data['new']['remote_ips'];
				if($host_list == '') $host_list = '%';
			}
			if($host_list != '') $host_list .= ',';
			$host_list .= 'localhost';

			// REVOKES and DROPS have to be done on old host list, not new host list
			$old_host_list = '';
			if($data['old']['remote_access'] == 'y') {
				$old_host_list = $data['old']['remote_ips'];
				if($old_host_list == '') $old_host_list = '%';
			}
			if($old_host_list != '') $old_host_list .= ',';
			$old_host_list .= 'localhost';

			// Create the database user if database was disabled before
			if($data['new']['active'] == 'y') {
				if($db_user) {
					if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);
				}
				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', true);
				}
			} else if($data['new']['active'] == 'n' && $data['old']['active'] == 'y') { // revoke database user, if inactive
					if($old_db_user) {
						if($old_db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);


							//$this->process_host_list('DROP', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $old_host_list, $link);
							//$this->process_host_list('REVOKE', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $old_host_list, $link);
						}
					}
					if($old_db_ro_user && $data['old']['database_user_id'] != $data['old']['database_ro_user_id']) {
						if($old_db_ro_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $old_host_list);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);

							//$this->process_host_list('DROP', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $old_host_list, $link);
							//$this->process_host_list('REVOKE', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $old_host_list, $link);
						}
					}
					// Database is not active, so stop processing here
					$link->query('FLUSH PRIVILEGES;');
					$link->close();
					return;
				}

			//* selected Users have changed
			if($data['new']['database_user_id'] != $data['old']['database_user_id']) {
				if($data['old']['database_user_id'] && $data['old']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($old_db_user) {
						if($old_db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);

							//$this->process_host_list('DROP', $data['new']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $old_host_list, $link);
							//$this->process_host_list('REVOKE', $data['new']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $old_host_list, $link);
						}
					}
				}
				if($db_user) {
					if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);
				}
			}
			if($data['new']['database_ro_user_id'] != $data['old']['database_ro_user_id']) {
				if($data['old']['database_ro_user_id'] && $data['old']['database_ro_user_id'] != $data['new']['database_user_id']) {
					if($old_db_ro_user) {
						if($old_db_ro_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);

							//$this->process_host_list('DROP', $data['new']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $old_host_list, $link);
							//$this->process_host_list('REVOKE', $data['new']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $old_host_list, $link);
						}
					}
				}
				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', true);
				}
			}

			//* Remote access option has changed.
			if($data['new']['remote_access'] != $data['old']['remote_access']) {

				//* revoke old priveliges
				//mysql_query("REVOKE ALL PRIVILEGES ON ".mysql_real_escape_string($data["new"]["database_name"],$link).".* FROM '".mysql_real_escape_string($data["new"]["database_user"],$link)."';",$link);

				//* set new priveliges
				if($data['new']['remote_access'] == 'y') {
					if($db_user) {
						if($db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							$this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link);
						}
					}
					if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
						if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link, '', true);
					}
				} else {
					if($old_db_user) {
						if($old_db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $data['old']['remote_ips']);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);

							//$this->process_host_list('DROP', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['old']['remote_ips'], $link);
							//$this->process_host_list('REVOKE', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['old']['remote_ips'], $link);
						}
					}
					if($old_db_ro_user && $data['old']['database_user_id'] != $data['old']['database_ro_user_id']) {
						if($old_db_ro_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $data['old']['remote_ips']);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);

							//$this->process_host_list('DROP', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['old']['remote_ips'], $link);
							//$this->process_host_list('REVOKE', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['old']['remote_ips'], $link);
						}
					}
				}
				$app->log('Changing MySQL remote access privileges for database: '.$data['new']['database_name'], LOGLEVEL_DEBUG);
			} elseif($data['new']['remote_access'] == 'y' && $data['new']['remote_ips'] != $data['old']['remote_ips']) {
				//* Change remote access list
				if($old_db_user) {
					if($old_db_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						// Find out users to drop and users to revoke
						$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $data['old']['remote_ips']);
						if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
						if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
					}
				}
				if($db_user) {
					if($db_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						$this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link);
					}
				}

				if($old_db_ro_user && $data['old']['database_user_id'] != $data['old']['database_ro_user_id']) {
					if($old_db_ro_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						// Find out users to drop and users to revoke
						$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $data['old']['remote_ips']);
						if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
						if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
					}
				}

				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						$this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link, '', true);
					}
				}
			}

			$link->query('FLUSH PRIVILEGES;');
			$link->close();
		}

	}

	function db_delete($event_name, $data) {
		global $app, $conf;

		if($data['old']['type'] == 'mysql') {
			if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
				return;
			}

			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to mysql: '.$link->connect_error, LOGLEVEL_ERROR);
				return;
			}

			$old_host_list = '';
			if($data['old']['remote_access'] == 'y') {
				$old_host_list = $data['old']['remote_ips'];
				if($old_host_list == '') $old_host_list = '%';
			}
			if($old_host_list != '') $old_host_list .= ',';
			$old_host_list .= 'localhost';

			if($data['old']['database_user_id']) {
				$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['old']['database_user_id']) . "'");
				$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
				if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
				if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
			}
			if($data['old']['database_ro_user_id']) {
				$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['old']['database_ro_user_id']) . "'");
				$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $old_host_list);
				if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
				if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
			}


			if($link->query('DROP DATABASE '.$link->escape_string($data['old']['database_name']))) {
				$app->log('Dropping MySQL database: '.$data['old']['database_name'], LOGLEVEL_DEBUG);
			} else {
				$app->log('Error while dropping MySQL database: '.$data['old']['database_name'].' '.$link->error, LOGLEVEL_WARNING);
			}

			$link->query('FLUSH PRIVILEGES;');
			$link->close();
		}


	}


	function db_user_insert($event_name, $data) {
		global $app, $conf;
		// we have nothing to do here, stale user accounts are useless ;)
	}

	function db_user_update($event_name, $data) {
		global $app, $conf;

		if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
			$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
			return;
		}

		//* Connect to the database
		$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
		if ($link->connect_error) {
			$app->log('Unable to connect to mysql'.$link->connect_error, LOGLEVEL_ERROR);
			return;
		}


		if($data['old']['database_user'] == $data['new']['database_user'] && ($data['old']['database_password'] == $data['new']['database_password'] && $data['new']['max_user_connections'] == $data['old']['max_user_connections'] && $data['new']['max_queries_per_hour'] == $data['old']['max_queries_per_hour'] && $data['new']['max_updates_per_hour'] == $data['old']['max_updates_per_hour'] && $data['new']['max_connections_per_hour'] == $data['old']['max_connections_per_hour'] || $data['new']['database_password'] == '')) {
			return;
		}


		$host_list = array('localhost');
		// get all databases this user was active for
		$db_list = $app->db->queryAllRecords("SELECT `remote_access`, `remote_ips` FROM `web_database` WHERE `database_user_id` = '" . intval($data['old']['database_user_id']) . "'");
		if(count($db_list) < 1) return; // nothing to do on this server for this db user

		foreach($db_list as $database) {
			if($database['remote_access'] != 'y') continue;

			if($database['remote_ips'] != '') $ips = explode(',', $database['remote_ips']);
			else $ips = array('%');

			foreach($ips as $ip) {
				$ip = trim($ip);
				if(!in_array($ip, $host_list)) $host_list[] = $ip;
			}
		}

		foreach($host_list as $db_host) {
			if($data['new']['database_user'] != $data['old']['database_user']) {
				$link->query("RENAME USER '".$link->escape_string($data['old']['database_user'])."'@'$db_host' TO '".$link->escape_string($data['new']['database_user'])."'@'$db_host'");
				$app->log('Renaming MySQL user: '.$data['old']['database_user'].' to '.$data['new']['database_user'], LOGLEVEL_DEBUG);
			}

			if($data['new']['database_password'] != $data['old']['database_password'] && $data['new']['database_password'] != '') {
				$link->query("SET PASSWORD FOR '".$link->escape_string($data['new']['database_user'])."'@'$db_host' = '".$link->escape_string($data['new']['database_password'])."';");
				$app->log('Changing MySQL user password for: '.$data['new']['database_user'].'@'.$db_host, LOGLEVEL_DEBUG);
			}

			if($data['new']['max_user_connections'] != $data['old']['max_user_connections'] OR $data['new']['max_queries_per_hour'] != $data['old']['max_queries_per_hour'] OR $data['new']['max_updates_per_hour'] != $data['old']['max_updates_per_hour'] OR $data['new']['max_connections_per_hour'] != $data['old']['max_connections_per_hour']) {
				if($data['new']['max_user_connections'] == "-1" OR !isset($data['new']['max_user_connections']) OR empty($data['new']['max_user_connections'])) { $data['new']['max_user_connections'] = 0; }
				if($data['new']['max_queries_per_hour'] == "-1" OR !isset($data['new']['max_queries_per_hour']) OR empty($data['new']['max_queries_per_hour'])) { $data['new']['max_queries_per_hour'] = 0; }
				if($data['new']['max_updates_per_hour'] == "-1" OR !isset($data['new']['max_updates_per_hour']) OR empty($data['new']['max_updates_per_hour'])) { $data['new']['max_updates_per_hour'] = 0; }
				if($data['new']['max_connections_per_hour'] == "-1" OR !isset($data['new']['max_connections_per_hour']) OR empty($data['new']['max_connections_per_hour'])) { $data['new']['max_connections_per_hour'] = 0; }
				
				$link->query("GRANT USAGE ON *.* TO '".$link->escape_string($data['new']['database_user'])."'@'$db_host' WITH MAX_USER_CONNECTIONS ".$data['new']['max_user_connections']." MAX_QUERIES_PER_HOUR ".$data['new']['max_queries_per_hour']." MAX_UPDATES_PER_HOUR ".$data['new']['max_updates_per_hour']." MAX_CONNECTIONS_PER_HOUR ".$data['new']['max_connections_per_hour'].";");
				$app->log('Changing MySQL Account Resource Limits for: '.$data['new']['database_user'].'@'.$db_host, LOGLEVEL_DEBUG);
			}
		}

		$link->query('FLUSH PRIVILEGES;');
		$link->close();

	}

	function db_user_delete($event_name, $data) {
		global $app, $conf;

		if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
			$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
			return;
		}

		//* Connect to the database
		$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
		if ($link->connect_error) {
			$app->log('Unable to connect to mysql'.$link->connect_error, LOGLEVEL_ERROR);
			return;
		}

		$host_list = array();
		// read all mysql users with this username
		$result = $link->query("SELECT `User`, `Host` FROM `mysql`.`user` WHERE `User` = '" . $link->escape_string($data['old']['database_user']) . "' AND `Create_user_priv` = 'N'"); // basic protection against accidently deleting system users like debian-sys-maint
		if($result) {
			while($row = $result->fetch_assoc()) {
				$host_list[] = $row['Host'];
			}
			$result->free();
		}

		foreach($host_list as $db_host) {
			if($link->query("DROP USER '".$link->escape_string($data['old']['database_user'])."'@'$db_host';")) {
				$app->log('Dropping MySQL user: '.$data['old']['database_user'], LOGLEVEL_DEBUG);
			}
		}

		$link->query('FLUSH PRIVILEGES;');
		$link->close();
	}

} // end class

?>
