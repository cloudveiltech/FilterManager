sh start-dev-instance.sh

docker-compose -f dev-instance.yaml exec php-apache bash -c "until nc -z database 3306; do sleep 1; false; done"

# TODO: Copy mysql file to mysql server and deploy it.

docker-compose -f dev-instance.yaml exec php-apache bash -c "cd /var/www/CitadelManager; php artisan key:generate; php artisan migrate; php artisan passport:install; php artisan db:seed; chown -R www-data:www-data storage/logs; cd -"

# Your dev instance should now be linked to the volume and ready for development.
