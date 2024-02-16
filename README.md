# Spiteful-Chat

A basic instant messaging chat system that utilizes WebSockets in both PHP (server) and JavaScript (client).

## Storage

Spiteful-Chat uses a MySQL Server set-up [relational database](https://cloud.google.com/learn/what-is-a-relational-database) to store user profiles, chats, media designations, and messages.

The real media data is saved to /private/spiteful-chat/chats/media/

## Hosting

The steps to host your own version of this repository are as follows:

1. Download the zipped source code and unpack within your virtual host environment
2. Import _database.sql_ into a new MySQL database and install dependencies using [composer](https://getcomposer.org/download/) `$ composer install` and [npm](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm) `$ npm install`
3. Create a /private/spiteful-chat/ directory at the **same level** as your DOCUMENT_ROOT directory
4. Create /private/spiteful-chat/database.php:

```
<?php $GLOBALS["connection"] = new mysqli($host, $username, $password, $database);
```

**Note:** You may need to change the required resource in websocket/spiteful-chat.php to match your full path as $\_SERVER["DOCUMENT_ROOT"] cannot be used there

5. Create /private/spiteful-chat/private-variables.php

```
<?php

// Google - Used for Google services
$googleClientId = "your-client-id.apps.googleusercontent.com";
$googleLoginUri = "https://your-domain/spiteful-chat/cwg.php";

// Vapid - Used for notifications

/* Generate using
require "vendor/autoload.php";
use Minishlink\WebPush\VAPID;
var_dump(VAPID::createVapidKeys()); */

$vapidPublic = "vapid-generated-public-key-should-be-longer-than-private-key";
$vapidPrivate = "vapid-generated-private-key";

// Session - Used for JWT signature validation
$sessionKey = ""; // Generate using bin2hex(random_bytes(32))
```

6. Create /private/spiteful-chat/chats/media/ directory
7. Ensure PHP / Apache / Nginx (typically www-data) has appropriate read/write permissions to the new directories (often overlooked)
8. If you are using Apache enable both the proxy and proxy-wstunnel modules
9. Add the following to your SSL virtual host (Apache) or server block (Nginx) (usually port 443):

For an **Apache** setup

```
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/_ws_/
RewriteCond %{HTTP:Upgrade} websocket [NC]
RewriteCond %{HTTP:Connection} upgrade [NC]
RewriteRule ^/_ws_/(.*) ws://127.0.0.1:12345/$1 [P,L]

ProxyPass /_ws_/ ws://127.0.0.1:12345
ProxyPassReverse /_ws_/ ws://127.0.0.1:12345
```

For a **Nginx** setup

```
location ~ /_ws_/* {
  rewrite ^/_ws_/(.*)$ /$1 break;
  proxy_pass http://127.0.0.1:12345;
  proxy_http_version 1.1;
  proxy_set_header Upgrade $http_upgrade;
  proxy_set_header Connection 'upgrade';
  proxy_set_header Host $host;
  proxy_cache_bypass $http_upgrade;
}
```

Get more information about this configuration [here](https://httpd.apache.org/docs/2.4/mod/mod_proxy_wstunnel.html) for Apache or [here](https://www.nginx.com/blog/websocket-nginx/) for Nginx

10. Run `$ php websocket/spiteful-chat.php` to start the websocket server

## Contributing

We welcome contributions to enhance the functionality and stability this project. Whether you're a developer, designer, or just an enthusiast, you can help make this project better.

Feel free to submit issues with questions, bug reports, feature requests, or the like.

If you want to contribute code here are the steps to get started:

1. Fork this project
2. Clone your fork of this project
3. Depending upon your situation follow the hosting steps
4. Create a new branch with an appropriate name
5. Make your changes or additions
6. Test and debug your changes
7. Make commits with detailed names and/or descriptions
8. Push your commits to your fork
9. Open a pull request
10. Be ready to make any necessary changes required by the reviewers

**Whitespace Rules**

This project enforces whitespace rules using [prettier](https://prettier.io/).

Run the following before each commit:

```
npx prettier . --write
```
