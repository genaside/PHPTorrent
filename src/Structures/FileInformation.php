<?php

namespace genaside\PHPTorrent\Structures;

/**
 * File Infomation Structure
 */
class FileInformation
{

    /**
     * The full filename relative to torrent's info name.
     * @note a null or empty name means that this is the only file
     * and takes up the name in torrent info.
     * @var String
     */
    public $name;

    /**
     * The size of the file in bytes.
     * @var int
     */
    public $size;

    /**
     * @var int
     */
    public $completed;

    /* ----- ? ----- */

    /**
     * Whether this file should be downloaded or skipped
     * @var Bool
     */
    public $active;
}
