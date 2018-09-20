#!/bin/bash

set -ex;

cd application/tests/ ;
../vendor/bin/phpunit ;
