Roll'n API
==========

This is a demonstration application of Apigility with Doctrine ORM.

Installation
------------

These commands will download the repository, composer, install
dependencies, then run the application locally.

```
git clone git@github.com:StukiOrg/RollNApi
cd RollNApi
php -r "readfile('https://getcomposer.org/installer');" | php
./composer.phar install
cp config/autoload/local.php.dist config/autoload/local.php
php -S localhost:8080 -t public public/index.php
```

Interact with the application with these API endpoints using
HTTP requests:

```
/api/artist
/api/album
/api/loop
```

The artist data is prepopulated with 100 entries, the album has
1000 entries and loop has 2.

