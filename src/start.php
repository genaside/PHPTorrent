<?php

//ini_set("default_socket_timeout", 5);

date_default_timezone_set( 'UTC' ); 

// a i might do auto loader later
require_once(__DIR__ . "/torrent/torrent.php");
require_once(__DIR__ . "/bencode/bencode.php");
require_once(__DIR__ . "/daemon/daemon.php");
require_once(__DIR__ . "/daemon/operation.php");
require_once(__DIR__ . "/config/config.php");
require_once(__DIR__ . "/storage/storage.php");
require_once(__DIR__ . "/database/database.php");


require_once(__DIR__ . "/structures/PeerInformation.php");
require_once(__DIR__ . "/structures/AnnounceInformation.php");
require_once(__DIR__ . "/structures/FileInformation.php");
require_once(__DIR__ . "/structures/TorrentInformation.php");

require_once(__DIR__ . "/structures/TorrentInformationList.php");
require_once(__DIR__ . "/structures/FileInformationList.php");
require_once(__DIR__ . "/structures/AnnounceInformationList.php");
require_once(__DIR__ . "/structures/PeerInformationList.php");

require_once(__DIR__ . "/logger/logger.php");

// Third party software
require_once(__DIR__ . "/../../includes/SpectrumLib/BitArray.php");

// start Daemon and detach,
//passthru( "php ./daemon/daemon.php" );

$d = new Daemon;
$d->start();