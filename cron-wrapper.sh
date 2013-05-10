#!/bin/bash

CURRENT_PATH_ARRAY=( `pwd | tr '/' ' '`)
SITE_NAME=${CURRENT_PATH_ARRAY[2]}
LOG_BASE_PATH="/var/www/${SITE_NAME}/ax/import/processed"
LOG_FILE="import_`date +'%H'`.log"
DAY=`LC_ALL=C date +'%A'`
IMPORT_FILE=$1

if [ ! -d ${LOG_BASE_PATH}/${DAY} ]; then
  mkdir -p ${LOG_BASE_PATH}/${DAY}
fi

php cron.php ${IMPORT_FILE} > ${LOG_BASE_PATH}/${DAY}/${LOG_FILE} 2>&1
