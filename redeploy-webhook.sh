#!/bin/bash

bin/magento maintenance:enable

rm -rf var/cache/* generated/* var/page_cache/* var/view_preprocessed/* pub/static/frontend/*

bin/magento cron:remove

git checkout -- .

git pull

chmod 777 $0

composer install

bin/magento setup:upgrade

bin/magento setup:di:compile

bin/magento setup:static-content:deploy -f

bin/magento cache:flush

bin/magento cron:install

bin/magento maintenance:disable

bin/magento deploy:mode:set production

ENVIRONMENT=$1

#echo 'Triggering Test Automation for the '${ENVIRONMENT}' environment'

#curl https://jenkins-fxo.web.fedex.com:8443/jenkins/job/fxo-ecommerce-platform-3537131/job/test-automation/buildWithParameters?token=11764edb32cf871116ab957cc8e113fcc7&env=${ENVIRONMENT}

