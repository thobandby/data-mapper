#!/usr/bin/env bash

set -euo pipefail

if [ -z "${SONAR_TOKEN}" ]; then
  echo "SONAR_TOKEN is not set"
  exit 1
fi

# Local-only helper for running SonarQube against the docker-compose setup.
XDEBUG_MODE=coverage vendor/bin/phpunit \
  --coverage-filter packages/core/src \
  --coverage-filter packages/cli-adapter/src \
  --coverage-filter packages/doctrine-adapter/src \
  --coverage-filter packages/symfony-adapter/src \
  --coverage-filter apps/demo-symfony/src \
  --coverage-clover coverage.xml \
  --coverage-html coverage \
  --log-junit phpunit-report.xml

docker run --rm \
  --network sonar_sonarnet \
  -e SONAR_HOST_URL="http://sonarqube:9000" \
  -e SONAR_TOKEN="${SONAR_TOKEN}" \
  -v "$(pwd):/usr/src" \
  sonarsource/sonar-scanner-cli
