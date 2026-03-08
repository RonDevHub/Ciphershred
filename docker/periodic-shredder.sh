#!/bin/sh
while true; do
  php /var/www/html/api/cron-shredder.php
  sleep 900
done