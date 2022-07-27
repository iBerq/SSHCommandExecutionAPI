<?php
include 'database.php';

$cron_jobs = select(array("*"), "cron_jobs");

$output = "";
foreach ($cron_jobs as $cron) {
    $cron = get_object_vars($cron);
    $output .= $cron["schedule"] . " php -f /var/www/html/cron.php id=" . $cron["id"] . PHP_EOL;
}

file_put_contents('/var/www/html/crontab.txt', $output);
echo exec('crontab -u www-data /var/www/html/crontab.txt');
echo exec('rm /var/www/html/crontab.txt');
echo "Crons loaded.\n";
