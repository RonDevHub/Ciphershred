#!/bin/sh
# Endlosschleife, die alle 15 Minuten den PHP-Shredder triggert
while true; do
  php /var/www/html/api/cron-shredder.php
  sleep 900
done