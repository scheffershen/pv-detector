STAN         = ./vendor/bin/phpstan

.PHONY: install
install:
	a2enmod rewrite
	apt install php-imagick
	apt install php7.4-amqp php7.4-bcmath
	systemctl restart apache2

.PHONY: docker
docker:
	sudo apt update -yqq
	sudo apt install apt-transport-https ca-certificates curl software-properties-common -yqq
	curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
	sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu bionic stable"
	sudo apt update -yqq
	apt-cache policy docker-ce
	sudo apt install docker-ce -yqq
	sudo systemctl status docker
	apt install docker-compose
	docker-compose up -d

.PHONY: up
up:
	svn up config/ src/ templates/ translations/ public/
	php bin/console cache:clear --no-warmup --env=prod
	php bin/console doctrine:schema:update --force
	chown www-data:www-data -R var data
	chmod +777 var data
	@echo "Site is updated"

.PHONY: doctrine
doctrine:
	php bin/console doctrine:schema:update --force

.PHONY: entity
entity:
	php bin/console make:entity		

.PHONY: crud
crud:
	php bin/console make:crud		

.PHONY: cache
cache:
	php bin/console cache:clear

phpunit:
	php vendor/bin/phpunit

behat:
	php vendor/bin/behat

vision:
	php bin/console app:google-vision-test		

.PHONY: debug
debug:
	tail -f var/log/prod.log	

stan: ## Run PHPStan
	$(STAN) analyse -c phpstan.neon --memory-limit 1G	