ISPConfig MySQL limits
=========================


# INSTALLATION (as root)

```
git clone https://github.com/alexalouit/ISPConfig-MySQL-limits.git
cd ISPConfig-MySQL-limits
php -q install.php
```

After install, a new tab (options) will be available in editing mysql user with an admin account.


## COMPATIBILITY

ISPConfig (select version in branch)


## NOTES

For multiple reasons, it works on RW users only


## MANUAL INSTALLATION

- patch ISPConfig
```
cp ispconfig.patch /usr/local/ispconfig/ispconfig.patch
cd /usr/local/ispconfig
patch -p3 < ./ispconfig.patch
rm ./ispconfig.patch
```

- sql queries
```
ALTER TABLE `web_database_user` ADD `max_user_connections` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_queries_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_updates_per_hour` bigint(20) NOT NULL DEFAULT '-1';
ALTER TABLE `web_database_user` ADD `max_connections_per_hour` bigint(20) NOT NULL DEFAULT '-1';
```