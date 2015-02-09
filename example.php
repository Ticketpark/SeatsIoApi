<?php

require_once('vendor/autoload.php');

$secretKey = 'yourSecretKey';
$seatsIo = new \Ticketpark\SeatsIo\SeatsIo($secretKey);

print $seatsIo->getCharts();