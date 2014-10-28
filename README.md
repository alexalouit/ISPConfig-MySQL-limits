ISPConfig MySQL limits
=========================


# INSTALLATION (as root)

git clone https://github.com/alexalouit/ISPConfig-MySQL-limits.git

cd ISPConfig-MySQL-limits

php -q install.php

After install, a new tab (options) will be available in editing mysql user with an admin account


## COMPATIBILITY

ISPConfig 3.0.5.4p4 or newer


## NOTES

Normally it should work in a multiple server environment (I haven't tested yet)

For multiple reasons, it works on RW users only


## TODO

For the moment, this will work only if the account is default user of database

Works with aps ?

Create template


## MANUAL INSTALLATION

```
make backup for each files

copy new file:
/usr/local/ispconfig/interface/web/sites/templates/database_user_edit.htm

copy modified files:
/usr/local/ispconfig/interface/web/sites/form/database_user.tform.php
/usr/local/ispconfig/interface/web/sites/lib/lang/*_database_user.lng
/usr/local/ispconfig/server/plugins-available/mysql_clientdb_plugin.inc.php

sql queries:
ALTER TABLE `web_database_user` ADD `max_user_connections` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_queries_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_updates_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_connections_per_hour` bigint(20) NOT NULL DEFAULT '-1';
```