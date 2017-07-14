# Developing Locally

*This needs to be expanded*

## Self-Signed Certificate

Shopify requires apps be secured over HTTPS now. The easist way to develop locally with SSL is to self-sign a certificate.

Example:

```bash
openssl req -new -sha256 \
    -key ssl/domain.key \
    -subj "/C=US/ST=CA/O=Acme, Inc./CN=localhost.ssl" \
    -reqexts SAN \
    -config <(cat /etc/ssl/openssl.cnf \
        <(printf "\n[SAN]\nsubjectAltName=DNS:localhost.ssl")) \
    -out ssl/domain.csr
```

Replace `localhost.ssl` with a domain you wish to use for the app. If you're on OSX, change `/etc/ssl/openssl.cnf` with `/System/Library/OpenSSL/openssl.cnf`.

This will save `ssl/domain.key` and `ssl/domain.csr`. You can then add the `domain.csr` file to your OS/browser as a trusted root certificate so you do not receieve browser warnings.

As well, add `127.0.0.1    localhost.ssl` to your `/etc/hosts` file.

Next, you can use Laravel's Homestead or a simple Docker setup to use the SSL certificate and accept HTTPS traffic.

## Docker Setup (Basic)

After generating the above certificate into `ssl` directory, and accepting it as a trusted root certificate, we can build a Docker setup to use it.

### Nginx Config

Create `default.conf` in the root of your Laravel project with:

```conf
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    return 302 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name _;

    ssl on;
    ssl_certificate /etc/nginx/ssl/server.crt;
    ssl_certificate_key /etc/nginx/ssl/server.key;

    add_header X-Frame-Option ALLOWALL;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header p3p 'CP="Not used"';

    root /var/www/html/public;
    index index.php;
 
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
 
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

In the above, we are setting up PHP-FPM, certificate serving, redirecting :80 to :443, pointing root to Laravel's `public` directory, and setting up some headers.

### Docker Compose

Create a `docker-compose.yml` in the root of your Laravel project with:

```yaml
nginx:
    image: nginx:latest
    ports:
        - '8000:443'
    volumes:
        - $PWD/default.conf:/etc/nginx/conf.d/default.conf
        - $PWD:/var/www/html
        - $PWD/ssl:/etc/nginx/ssl
    links:
        - php
    restart: always
php:
    image: php:7-fpm
    volumes:
        - $PWD:/var/www/html
```

In the above we do the following:

1. Pull the `nginx` image
2. Bind port 8000 on host to 443 on Docker
3. Mount the `default.conf` we created earlier to Nginx
4. Mount our code to `/var/www/html`
5. Mount our certificates created previouly in `ssl` directory
6. Pull the `php` image
7. Mount our code to `/var/www/html` so PHP-FPM can see the code as well

You may optionally add in the MySQL or Postgres images for a database, if not, you can setup Laravel to use a SQLite file. Be sure to adjust your app's `.env` file to use the SQLite file or other database credentials.

### SQLite Database

If you choose not to pull in a database image for Docker, its easy to just use SQLite.

`touch databases/database.sqlite` and change `.env` to have `DB_CONNECTION=sqlite`

You will now have a database to use.

## Running

Once above two steps are done, see the `README.md` on installing this package.

You should now have:

- Package installed
- Self-signed certificate setup and installed
- `docker-compose.yml` file
- `default.conf` file

When finished installing this package, run `docker-compose up`.

You should be able to access `https://localhost.ssl:8000/login` and see the app login screen.

Again, this is very basic and to be used as a guide. You can achieve the same results using Homstead, Vagrant, etc.
