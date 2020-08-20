
# Cronnit

A free service for making scheduled posts to Reddit. It's available at
https://cronnit.us or you can download the code and host it yourself!

## Installation

### Quick Install

If you have a fresh server you can run the install script:

    wget https://raw.githubusercontent.com/krisives/cronnit.us/master/install.sh
    nano install.sh # Enter your hostname, client_id, etc.
    sudo ./install.sh

This will:

* Install all the packages needed for Apache, MySQL, PHP, etc.
* Create a 2GB swapfile to ensure the server doesn't run out of memory
* Create the Linux user `cronnit`
* Create a `cronnit` MySQL database
* Create a `cronnit` MySQL user (with a secure random 30 character password)
* Grant permissions for `cronnit` to use the database
* Import the Cronnit SQL schema
* Disable insecure Apache modules (`mod_status`, `mod_userdir`)
* Acquire an SSL certificate from LetsEncrypt using `certbot`
* Configure Apache to run Cronnit including the SSL certificate
* Downloads the latest version of Cronnit from GitHub
* Generates a `config.php` script for Cronnit
* Installs all of the composer dependencies
* Creates a CRON job to run the `cron.sh` script
* Creates a CRON job to update from GitHub every day
* Configures the system to install security updates automatically

Before running the script you should ensure the DNS record for your domain name
point to the IP running this server.

For `client_id` and `client_secret` you will need to
[create a Reddit app](https://www.reddit.com/prefs/apps) using a redirect URI
of `https://example.com/authorize` ensure you are using `https://` in the URI.

You can skip using `https://` and `certbot` if you want by running the script
with the `HTTP_ONLY` environment variable set:

    sudo HTTP_ONLY=1 ./install.sh

### Development Server

If you want to run Cronnit using the PHP development server:

    sudo apt install php-cli php-sqlite3 composer
    git clone git@github.com:/krisives/cronnit.us.git
    cd cronnit
    composer update
    cp config.php.example config.php
    nano config.php
    cd public_html/
    php -S localhost:8080

#### Configuring Reddit Application for Dev Server

If you're running cronnit via the PHP development server, it's likely
slightly simpler than a full-fledged server.

For the configuration (certbot, etc), if you're not keen to set this up,
(or your server isn't actually public facing) you can get away with running
via `http`, by specifying `http` urls (rather than `https`) in `config.php`
and your reddit app's redirect uri.

- Set up a reddit application for oauth:
  - From https://www.reddit.com/prefs/apps 
    - Create an app, it should be a web-app. 
      - Name it whatever.
      - For the redirect URI, choose the __exact same address and port as 
        you specified in `config.php`__, suffixed by `/authorize`.
        - In our case, that should be:
          `http://localhost:8080/authorize`.
      - The about URL and description don't really matter.
  - The `client_id` should appear below your application name/to the right of 
    the icon on the [apps page](https://www.reddit.com/prefs/apps).

#### Setting Up the Development Server on a fresh VM

Tested on:

```
vagrant 2.2.9
VirtualBox 6.1.6 r137129
```

(In my experience) VirtualBox >= 6.1.8 seems to hang on `vagrant up`.
[See this ticket](https://www.virtualbox.org/ticket/19642#comment:6)

- Install [`vagrant`](https://www.vagrantup.com/downloads) and
[`VirtualBox` 6.1.6](https://www.virtualbox.org/wiki/Download_Old_Builds_6_1)*.
- From within this project (where the `vagrantfile` lives):
  - install the vm with:
    ```bash
    vagrant up
    ```
  - Log into the vm with:
    ```bash
    vagrant ssh
    ```
    You should find yourself logged in as `cronnit@ubuntu-focal`.
  - Edit config.php:
    ```bash
    vim config.php
    ```
    Replace:
    - `'url' => 'http://my.hostname.com'` with `'url' => 'http://localhost:8080'`
    - `'client_id => 'xxxx`, `'client_secret' => 'xxxx'` with those obtained from
      your [reddit app](https://www.reddit.com/prefs/apps).

##### Running Server on Vagrant VM

- To make the site you serve accessible by the host (from a browser on your main computer):
  ```bash
  $ lout &
  ```
  (This is simply an alias for some `socat` redirection)
- To run the website:
  ```bash
  serve
  ```
  (This just navigates to `public_html`, and calls `php -S localhost:8080`).
- To view the website, from a browser on your host, navigate to `http://localhost:8080` !
- To refresh changes, you'll need to interrupt  `serve` (`Ctrl`+`c` ) and call it again `serve`.

## Donate

If you find Cronnit useful as a tool or source please consider
[making a donation](https://cronnit.us/donate)!
