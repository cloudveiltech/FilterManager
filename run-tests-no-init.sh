docker-compose -f test-instance.yaml exec php-apache bash -c "cd /var/www/CitadelManager; vendor/bin/phpunit; cd -"
