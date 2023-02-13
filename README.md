README
===

## Développement Requirement

- symfony 5.0 | 5.4
- php 7.4 | 8.1
- MySQL 5.7 | 8.0
- symfony/encore
- bootstrap": "^4.4.1, jquery": "^3.5 
- sudo apt install php7.4-bcmath

## Install

Les commandes pour l'installation:

	$ composer update
	$ composer install
	$ php bin/console doctrine:database:create
	$ php bin/console doctrine:schema:update --force
    $ php bin/console doctrine:fixtures:load --env=prod --group PROD

## Configuration

Modifier les parametres suivants pour votre serveur local, voir .env.exemple

.env

```
DATABASE_URL=mysql://user:passwrod@localhost:3306/atu?serverVersion=5.7
BASE_URLS=http://localhost/
MAILER_URL=smtp://localhost:25?encryption=&auth_mode=

SITE_HELP=https://localhost/docs/index.html
nBASE_URLS=https://localhost/
REVUE_URL=https://localhost
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
ADMIN_EMAIL=xx@xxx.com
```

## crontab

il faut un seul crontab à la fois

    #php bin/console app:pdf-to-images
    #php bin/console app:pdfImages-to-textes

    php bin/console app:numero-traitement
    
    php bin/console app:dci-indexation
    php bin/console app:numero-indexation

    crontab -e

```
* * * * * php /var/www/interface/bin/console app:numero-traitement >> /var/www/interface/var/log/numero-traitement.log

* * * * * php /var/www/interface/bin/console app:dci-indexation >> /var/www/interface/var/log/dci-indexation.log
* * * * * php /var/www/interface/bin/console app:numero-indexation >> /var/www/interface/var/log/numero-indexation.log

0 0 * * * php /var/www/interface/bin/console app:alerte-fin-abonnement-revue >> /var/www/interface/var/log/alerte-fin-abonnement-revue.log
0 0 * * * php /var/www/interface/bin/console app:alerte-bilan-hebdomadaire >> /var/www/interface/var/log/alerte-bilan-hebdomadaire.log
```

## supervisord

supervisord a des problèmes. il n'arrive pas au bout, il y a une limite du temps

## Les commands pour mise à jour le site en production

    $ make up

## PDF To Image

GHOSTSCRIPT: 

    apt-get install -y poppler-utils ghostscript 

ImageMagick:     

https://computingforgeeks.com/how-to-install-php-on-ubuntu/

    sudo apt-get -y install gcc make autoconf libc-dev pkg-config
    sudo apt-get -y install libmagickwand-dev
    sudo pecl7.4 install imagick
    sudo bash -c "echo extension=imagick.so > /etc/php/7.4/conf.d/imagick.ini"

/etc/ImageMagick-6/policy.xml

```
<policy domain="coder" rights="read|write" pattern="PDF" />
```

    systemctl restart apache2

## Code Quality Tools

```
  $ mkdir --parents tools
  $ composer require --working-dir=tools friendsofphp/php-cs-fixer phpstan/phpstan
  $ tools/vendor/bin/php-cs-fixer fix src
  $ tools/vendor/bin/phpstan analyse src
  $ tools/vendor/bin/php-cs-fixer fix src --dry-run --verbose
  $ tools/vendor/bin/php-cs-fixer fix src --verbose
  $ cd tools
  $ npm init -y
  $ npm install --dev prettier-plugin-twig-melody
  $ ./node_modules/.bin/prettier --write "**/*.html.twig"
  $ ./node_modules/.bin/prettier --write "**/*.js"
```

## MySQL: Reset id column to auto increment from 1

```
SET @num := 0;
UPDATE indexation SET id = @num := (@num+1);
ALTER TABLE indexation AUTO_INCREMENT =1;
```

## python

    $ python3.9 -m venv env
    $ source env/bin/activate
    $ pip install -r requirements.txt
    $ python3 page_translation.py

    ## to do
    $ python page_translation.py # text lang detect() and text translate()
    # https://stackoverflow.com/questions/68214591/python-google-trans-new-translate-raises-error-jsondecodeerror-extra-data
    $ # text speak()

    ## to do
    $ # scrapper test()

## converting epub files to PDF format

https://askubuntu.com/questions/299747/converting-epub-files-to-pdf-format

    $ sudo apt-get install calibre
    $ ebook-convert file.epub file.pdf

## php7.4

    $ sudo apt install php7.4-amqp php7.4-bcmath php7.4-imagick php7.4-imap php7.4-xmlrpc php7.4-common php7.4-intl php7.4-mbstring php7.4-mysql php7.4-opcache php7.4-readline php7.4-soap php7.4-xml php7.4-xmlrpc php7.4-zip

## php8, mysql8 compatible check

    $ docker-compose -f docker-compose_8.yml build
    $ docker-compose -f docker-compose_8.yml up -d
    $ docker-compose -f docker-compose_8.yml ps

https://websiteforstudents.com/how-to-migrate-to-php-8-0-on-ubuntu/

    $ dpkg --get-selections | grep -i php
    $ sudo apt-get install software-properties-common
    $ sudo add-apt-repository ppa:ondrej/php
    $ sudo apt update
    $ sudo apt install php8.1-bcmath php8.1-bz2 php8.1-cli php8.1-fpm php8.1-common php8.1-curl php8.1-dev php8.1-gd php8.1-imagick php8.1-imap php8.1-intl php8.1-mbstring php8.1-mysql php8.1-opcache php8.1-readline php8.1-soap php8.1-xml php8.1-xmlrpc php8.1-zip
    $ sudo a2enmod proxy_fcgi setenvif
    $ sudo a2enconf php8.1-fpm
    $ sudo systemctl restart php8.1-fpm.service
    $ sudo apt update
    $ sudo apt install php8.1 libapache2-mod-php8.1
    $ sudo a2dismod php7.4
    $ sudo a2enmod php8.1
    $ sudo systemctl restart apache2.service

## Delete the dangling image

    $ docker rmi $(docker images -qf "dangling=true")    

## elasticsearch

    $ curl -fsSL https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
    $ echo "deb https://artifacts.elastic.co/packages/7.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-7.x.list
    $ sudo apt update
    $ sudo apt install elasticsearch
    $ sudo nano /etc/elasticsearch/elasticsearch.yml
        + network.host: localhost
    $ sudo systemctl start elasticsearch
    $ sudo systemctl enable elasticsearch
    $ netstat -ntlp
    $ curl -X GET 'http://localhost:9200'    

## certbot ubuntu-20-0cd ..

https://www.digitalocean.com/community/tutorials/how-to-secure-apache-with-let-s-encrypt-on-ubuntu-20-04-fr

## symfony session mysql table

```
CREATE TABLE `sessions` (
    `sess_id` VARBINARY(128) NOT NULL PRIMARY KEY,
    `sess_data` BLOB NOT NULL,
    `sess_lifetime` INTEGER UNSIGNED NOT NULL,
    `sess_time` INTEGER UNSIGNED NOT NULL,
    INDEX `sessions_sess_lifetime_idx` (`sess_lifetime`)
) COLLATE utf8mb4_bin, ENGINE = InnoDB;
```

