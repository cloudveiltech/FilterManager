FROM kfreezen/php-custom:v1

# Set up the test instance app.
ADD ./test-instance-vhost.conf /etc/apache2/sites-available/001-citadel.conf
ADD ./test-instance.env /var/www/CitadelManager/.env

# You'll need to provide your own server.crt and server.key for automated testing
COPY ./server.crt /etc/ssl/localhost.crt
COPY ./server.key /etc/ssl/private/localhost.key

# Enable Citadel site.
RUN a2dissite 000-default.conf
RUN a2ensite 001-citadel.conf
RUN service apache2 restart

ADD . /var/www
RUN chown -R www-data:www-data /var/www/CitadelManager/resources;\
	chown -R www-data:www-data /var/www/CitadelManager/app;\
	chown -R www-data:www-data /var/www/CitadelManager/bootstrap/cache;\
	chown -R www-data:www-data /var/www/CitadelManager/storage;\
	chown www-data:www-data /var/www/CitadelManager/.env;

RUN chmod -R 775 /var/www/CitadelManager/resources;\
	chmod -R 775 /var/www/CitadelManager/app;\
	chmod -R 775 /var/www/CitadelManager/bootstrap/cache;\
	chmod -R 775 /var/www/CitadelManager/storage;\
	chmod 775 /var/www/CitadelManager/.env;

ENV APACHE_DOCUMENT_ROOT /var/www/FilterManager/public

EXPOSE 80
EXPOSE 443
