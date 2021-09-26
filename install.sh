#/usr/bin/env bash

# This script will automatically install and configure everything on a Linux
# server needed to run Cronnit
#
# Only use this on a fresh server! Running this on a server that already has
# Apache or MySQL configured in a different way will probably break things
#
# It's safe to re-run this script

hostname="${CRONNIT_HOSTNAME:-my.hostname.com}"
client_id="${CRONNIT_CLIENT_ID:-XXXX}"
client_secret="${CRONNIT_CLIENT_SECRET:-XXXX}"
email="${CRONNIT_EMAIL:-myemail@gmail.com}"

# Make sure we are running as root
if [ $(whoami) != root ]; then
  echo "Must be ran as root"
  exit 1
fi

# Ensure we start in a known directory
cd /root

# Determine if the provided hostname is a subdomain or not
is_subdomain=0

if echo "$hostname" | grep '.*\..*\..*'; then
    is_subdomain=1
fi

# Ensure there is a swapfile on the system since some things like MySQL freak
# out under low memory conditions
if ! swapon -s | grep "/swapfile"; then
  echo "Creating swapfile..."
  fallocate -l 2G /swapfile
  mkswap /swapfile
  chmod 600 /swapfile
  swapon /swapfile

  if ! grep /swapfile /etc/fstab; then
    echo "/swapfile none swap sw 0 0" >> /etc/fstab
  fi
fi

# Fetch and install updates first
apt update
apt upgrade -y

# Install MySQL and some basic utilities
apt install -y \
    mariadb-server \
    apache2 \
    libapache2-mod-php \
    composer \
    moreutils \
    htop \
    zip unzip \
    git \
    pwgen \
    certbot

# Install PHP modules
apt install -y \
    php-xml \
    php-mysql \
    php-mbstring \
    php-zip \
    php-json \
    php-curl

# Disable mod_status for security reasons
a2dismod status

# Enable mod_rewrite for URL rewriting
a2enmod rewrite

# Enable mod_ssl for HTTPS
a2enmod ssl

# Disable userdir mod since it will disable PHP for user directories
a2dismod userdir

# Remove the default Apache landing page configuration
rm -f /etc/apache2/sites-enabled/000-default.conf

# Turn the web server off since we might be doing certbot stuff with a
# standalone HTTP server
service apache2 stop

if [ ! "$HTTP_ONLY" ]; then
    if [ "$is_subdomain" ]; then
        certbot certonly -n --agree-tos --email "$email" --standalone -d "$hostname"
    else
        certbot certonly -n --agree-tos --email "$email" --standalone -d "$hostname" -d "www.$hostname"
    fi

url="https://$hostname"
cat > /etc/apache2/sites-enabled/000-cronnit.conf <<EOL
ServerSignature Off
ServerTokens Prod

<VirtualHost *:80>
  ServerName $hostname
  ServerAlias www.$hostname
  Redirect permanent / https://$hostname/
</VirtualHost>

<VirtualHost *:443>
  ServerName $hostname
  ServerAlias www.$hostname
  DocumentRoot /home/cronnit/public_html

  <Directory /home/cronnit/public_html/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>

  SSLEngine on
  SSLCertificateFile "/etc/letsencrypt/live/$hostname/cert.pem"
  SSLCertificateKeyFile "/etc/letsencrypt/live/$hostname/privkey.pem"
  SSLCertificateChainFile "/etc/letsencrypt/live/$hostname/chain.pem"
</VirtualHost>
EOL
else
url="http://$hostname"
cat > /etc/apache2/sites-enabled/000-cronnit.conf <<EOL
ServerSignature Off
ServerTokens Prod

<VirtualHost *:80>
  ServerName $hostname
  ServerAlias www.$hostname
  DocumentRoot /home/cronnit/public_html

  <Directory /home/cronnit/public_html/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
EOL
fi

# Restart the apache2 service so module changes take effect
service apache2 restart

# Generate a random 30 character password used for cronnit database
if [ ! -f /root/cronnit.txt ]; then
    pwgen -s 30 1 > /root/cronnit.txt
fi

DBPASS=$(cat /root/cronnit.txt)

# Check if the cronnit user exists in MySQL
RESULT="$(mysql -uroot -sse "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = 'cronnit')")"

if [ "$RESULT" != 1 ]; then
    echo "Creating database user..."
    mysql -uroot -sse "CREATE USER 'cronnit'@'localhost' IDENTIFIED BY '$DBPASS';"
    mysql -uroot -sse "FLUSH PRIVILEGES;"
fi

# Create the cronnit database if needed and grant privileges
mysql -uroot -sse "CREATE DATABASE IF NOT EXISTS cronnit COLLATE 'utf8mb4_unicode_520_ci';"
mysql -uroot -sse "GRANT ALL PRIVILEGES ON cronnit.* TO 'cronnit'@'localhost';"
mysql -uroot -sse "FLUSH PRIVILEGES;"

# Download the cronnit SQL schema and import it
if [ ! -f /root/cronnit.sql ]; then
    wget -O /root/cronnit.sql "https://cronnit.us/cronnit.sql"
fi

mysql -uroot cronnit < /root/cronnit.sql

# Create the cronnit user
if [ ! -d /home/cronnit ]; then
    useradd -m cronnit
fi

cat > /home/cronnit/config.php <<EOL
<?php return [
  'client_id' => '$client_id',
  'client_secret' => '$client_secret',
  'dbdsn' => 'mysql:dbname=cronnit',
  'dbuser' => 'cronnit',
  'dbpass' => '$DBPASS',
  'url' => '$url'
];
EOL
chown cronnit:cronnit /home/cronnit/config.php

# Become the cronnit user and download cronnit
su cronnit <<EOF
    cd ~

    if [ ! -d .git ]; then
        git init
        git remote add origin https://github.com/krisives/cronnit.us
    fi

    git pull origin master
    git branch --set-upstream-to=origin/master master
    composer update

    if ! crontab -l | grep cron.sh; then
        (
            crontab -l 2> /dev/null;
            echo "* * * * * /home/cronnit/cron.sh";
            echo "@daily cd /home/cronnit && git pull && composer update";
        ) | crontab -
    fi
EOF

# Cleanup any packages waiting to be removed
apt autoremove -y

# Ensure the server gets automatic security upgrades
echo unattended-upgrades unattended-upgrades/enable_auto_updates boolean true | debconf-set-selections
dpkg-reconfigure -f noninteractive unattended-upgrades
