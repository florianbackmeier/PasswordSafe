# PasswordSafe
\- web application to store passwords and other information securely

## Features
* independent self-hostable application
* 2-Factor authentication with e.g. Google Authenticator
* sharing of passwords with other users
* PasswordSafe Inheritance/Trust person
* Material Design Style

## Security
* Your password is hashed with SHA256 via PBKDF2 which encrypts your data with Salsa20 (see src/PasswordSafeBundle/Security/EncryptionService.php)
* You need a good password! And don't forget it, you can't restore your data.
* If your server is compromised, your safety is gone, cause your memory is not safe.

## Requirements
- Webserver with SSL
- PHP

## Installation
1. Check out latest version
2. Setup database
 - Create parameters.yml in app/config
```
parameters:
  database_host: localhost  
  database_port: null  
  database_name: dbName  
  database_user: dbUser  
  database_password: dbPass
```
  - php bin/console doctrine:schema:update --force
3. Add 'secret' to parameters.yml (see http://symfony.com/doc/current/reference/configuration/framework.html#secret)
4. composer install (get composer from https://getcomposer.org/download/)
5. Set up nginx
```
server {
    listen   80;
    listen   [::]:80;
    server_name    passwordsafe.domain.tld;
    return         301 https://$server_name$request_uri;
}

server {
    listen   443;
    listen   [::]:443;
    server_name passwordsafe.domain.tld;

    ssl on;
    ssl_certificate /etc/ssl/myown/passwordsafe.domain.tld.pub;
    ssl_certificate_key /etc/ssl/myown/passwordsafe.domain.tld.key;

    root /var/www/passwordsafe.domain.tld/web;

    location / {
        try_files $uri /app.php$is_args$args;
    }
    location ~ ^/app\.php(/|$) {
        include fastcgi.conf;
        fastcgi_pass php;
        fastcgi_index index.php;

        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param  PHP_VALUE "session.cookie_httponly = 1\nsession.cookie_secure = 1\n";
        internal;
    }
}
```
6. php bin/console assets:install web --symlink
7. php bin/console cache:clear --env=prod --no-debug
8. chown --webserver user--::--webserver group-- . -R # replace with e.g. www-data
9. Create user in database: INSERT INTO Users (username) VALUES ('max')
