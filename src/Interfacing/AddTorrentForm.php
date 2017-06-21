<?php

namespace genaside\PHPTorrent\Interfacing;

/**
 * Class AddTorrentForm
 * @package genaside\PHPTorrent\Interfacing
 */
class AddTorrentForm
{
    /**
     * @var string
     */
    public $torrent_source_path;
    /**
     * @var string
     */
    public $download_destination;
    /**
     * @var int
     */
    public $active = 1;
}