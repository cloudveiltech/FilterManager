docker-compose -f test-instance.yaml stop -t 1
docker-compose -f test-instance.yaml rm -f
rm -r ./html

sudo chown -R $USER ./CitadelManager/storage

