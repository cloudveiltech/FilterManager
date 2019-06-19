#!/bin/sh

mkdir -p storage/framework/cache/data

cp storage/framework/cache/.gitignore storage/framework/cache/data/.gitignore

echo "*" > storage/framework/cache/.gitignore
echo "!data/" > storage/framework/cache/.gitignore
echo "!.gitignore" > storage/framework/cache/.gitignore

