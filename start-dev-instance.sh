# You'll have to provide these yourself.
cp ~/.ssl/server.key .
cp ~/.ssl/server.crt .

mkdir ./html # This is a shim for the PHP image we're using.

# Spin up test container.
docker-compose -f dev-instance.yaml pull
docker-compose -f dev-instance.yaml build $1
docker-compose -f dev-instance.yaml up -d

rm server.key
rm server.crt

cp dev-instance.env ./CitadelManager/.env

sudo chown -R 33 ./CitadelManager/storage

# Do final setup
echo Waiting for mysql server to initialize

