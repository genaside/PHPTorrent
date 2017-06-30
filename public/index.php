<?php

use genaside\PHPTorrent\Daemon\Daemon;

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

// Start Daemon and detach
// passthru("php ./daemon/daemon.php");

$d = new Daemon;
$d->start();