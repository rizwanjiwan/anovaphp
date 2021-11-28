#!/bin/sh
echo "Building dev image..."
docker build -t rizwanjiwan/anovaphpdev:1.0  -f ./dockerfiles/dev/Dockerfile ./
echo "Building prod image..."
docker build -t rizwanjiwan/anovaphp:1.0 -f ./dockerfiles/prod/Dockerfile ./