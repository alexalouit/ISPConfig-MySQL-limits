ISPConfig MySQL limits
=========================


# INSTALLATION (as root)

```
git clone https://github.com/alexalouit/ISPConfig-MySQL-limits.git
cd ISPConfig-MySQL-limits
php -q install.php
```

After install, a new tab (options) will be available in editing mysql user with an admin account


## COMPATIBILITY

ISPConfig 3.0.5.4p4 or newer


## NOTES

Normally it should work in a multiple server environment (I haven't tested yet)

For multiple reasons, it works on RW users only


## TODO

For the moment, this will work only if the account is default user of database

Works with aps ?


## MANUAL INSTALLATION

```
make backup for each files

copy new file:
/usr/local/ispconfig/interface/web/sites/templates/database_user_edit_advanced.htm

copy modified files:
/usr/local/ispconfig/interface/web/sites/form/database_user.tform.php
/usr/local/ispconfig/interface/web/sites/lib/lang/*_database_user.lng
/usr/local/ispconfig/server/plugins-available/mysql_clientdb_plugin.inc.php
/usr/local/ispconfig/interface/web/client/lib/lang/*_client_template.lng
/usr/local/ispconfig/interface/web/client/lib/lang/*_client.lng
/usr/local/ispconfig/interface/web/client/lib/lang/*_reseller.lng
/usr/local/ispconfig/interface/web/client/templates/client_template_edit_limits.htm
/usr/local/ispconfig/interface/web/client/templates/reseller_edit_limits.htm
/usr/local/ispconfig/interface/web/client/form/reseller.tform.php
/usr/local/ispconfig/interface/web/client/form/client.tform.php
/usr/local/ispconfig/interface/web/client/form/client_template.tform.php
/usr/local/ispconfig/interface/web/client/templates/client_edit_limits.htm

sql queries:
ALTER TABLE `web_database_user` ADD `max_user_connections` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_queries_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_updates_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_connections_per_hour` bigint(20) NOT NULL DEFAULT '-1';

ALTER TABLE `client` ADD `max_user_connections` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `client` ADD `max_queries_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `client` ADD `max_updates_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `client` ADD `max_connections_per_hour` bigint(20) NOT NULL DEFAULT '-1';

ALTER TABLE `client_template` ADD `max_user_connections` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `client_template` ADD `max_queries_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `client_template` ADD `max_updates_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `client_template` ADD `max_connections_per_hour` bigint(20) NOT NULL DEFAULT '-1';
```