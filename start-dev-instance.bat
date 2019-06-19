@echo off

echo %UserProfile%

REM # You'll have to provide these yourself.
copy "%UserProfile%\.ssl\server.key" .\server.key
copy "%UserProfile%\.ssl\server.crt" .\server.crt

REM This is a shim for the PHP image we're using
mkdir html
mkdir share

REM Spin up test container
docker-compose -f dev-instance.yaml pull
docker-compose -f dev-instance.yaml build %*
docker-compose -f dev-instance.yaml up -d

del server.key
del server.crt

copy dev-instance.env .\CitadelManager\.env

REM Do final setup
echo Waiting for mysql server to initialize
