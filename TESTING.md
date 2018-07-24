# Automated Test Setup

Automated test scripts require docker and docker-compose. Automated test scripts are also Linux+macOS only currently. If you have a need, feel free to port the test scripts to Windows batch files.

Installing docker for Linux: https://docs.docker.com/install/
Installing docker for macOS: https://docs.docker.com/docker-for-mac/install/
Installing docker for Windows: https://docs.docker.com/docker-for-windows/install/

Installing docker compose: https://docs.docker.com/compose/install/

You will also need to do build environment setup as specified in README.md

Once you have those prerequisites set up on your computer, running the tests should be as simple as running

```
sh run-tests.sh
```

The first time you spin up the test container, it will take about 5 minutes.