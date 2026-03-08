#!/bin/sh
while true; do
  # Der Pfad zur API-Datei hat sich geändert
  php /var/www/html/public/api/cron-shredder.php
  sleep 900
done