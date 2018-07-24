# You'll have to provide these yourself.
cp ~/.ssl/server.key .
cp ~/.ssl/server.crt .

# Spin up test container.
docker-compose -f test-instance.yaml pull
docker-compose -f test-instance.yaml build $1
docker-compose -f test-instance.yaml up -d

rm server.key
rm server.crt

# Do final setup
echo Waiting for mysql server to initialize

docker-compose -f test-instance.yaml exec php-apache bash -c "until nc -z database 3306; do sleep 1; false; done"

docker-compose -f test-instance.yaml exec php-apache bash -c "cd /var/www/CitadelManager; php artisan key:generate; php artisan migrate; php artisan passport:install; php artisan db:seed; chown -R www-data:www-data storage/logs; cd -"

# Your test instance should now be ready to run tests on.