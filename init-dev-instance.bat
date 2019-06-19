@echo off

call start-dev-instance.bat

docker-compose -f dev-instance.yaml exec php-apache bash -c "until nc -z database 3306; do sleep 1; false; done"

docker-compose -f dev-instance.yaml exec php-apache bash -c "cd /var/www/CitadelManager; php artisan key:generate; php artisan migrate; php artisan passport:install; php artisan db:seed; chown -R www-data:www-data storage/logs; cd -"
