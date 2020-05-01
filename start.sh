#!/bin/bash
docker rm -f sunny
docker run -d -p 3000:80 --name sunny sunny:dev