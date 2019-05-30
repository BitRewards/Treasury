#!/usr/bin/env bash
docker-compose -f docker-compose.yml exec php sh -c "cd /app/ && $*"
