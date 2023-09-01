# Spiteful-Chat

A basic instant messaging chat system that utilizes WebSockets in both PHP (server) and JavaScript (client).

## Storage

Spiteful-Chat uses a MySQL Server set-up [relational database](https://cloud.google.com/learn/what-is-a-relational-database) to store user profiles, chats, media designations, and messages.

The real media data is saved to /private/spiteful-chat/chats/media/

## Hosting

The steps to host your own version of this repository are as follows:
1. Download the zipped source code and unpack within your virtual host environment
2. Import *database.sql* into a new MySQL database and install dependencies using [composer](https://getcomposer.org/download/) `$ composer install` and [npm](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm) `$ npm install`
3. Create a /private/spiteful-chat/ directory at the same level as your DOCUMENT_ROOT directory
4. Create /private/spiteful-chat/database.php:
  
```
<?php $GLOBALS["connection"] = new mysqli($host, $username,$password, $database);
```

**Note:** You may need to change the required resource in websocket/spiteful-chat.php to match your full path as $_SERVER["DOCUMENT_ROOT"] cannot be used there

5. Create /private/spiteful-chat/google-client-id.php:
  
```
<?php $google_client_id = "your.google.client.id";
```

6. Create /private/spiteful-chat/chats/media/ directory
7. Ensure PHP / Apache (typically www-data) has appropriate read/write permissions to the new directories
8. If you are using Apache enable both the proxy and proxy-wstunnel modules
9. Add the following to your 443 virtual host:
```
# Websocket
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/_ws_/
RewriteCond %{HTTP:Upgrade} websocket [NC]
RewriteCond %{HTTP:Connection} upgrade [NC]
RewriteRule ^/_ws_/(.*) ws://localhost:12345/$1 [P,L]

ProxyPass /_ws_/ ws://localhost:12345
ProxyPassReverse /_ws_/ ws://localhost:12345
```
Get more information about this configuration [here](https://httpd.apache.org/docs/2.4/mod/mod_proxy_wstunnel.html)

10. Run `$ php websocket/spiteful-chat.php` to start the websocket server