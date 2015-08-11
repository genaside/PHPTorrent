<?php

/**
 * Torrent Infomation Structure
 */
class TorrentInformation{
    
    /**
     * The hash of the torrent's info.
     * Hash here is a 40-byte hex.
     * @note info_hash is no longer a 20byte binary string here.
     * @var String
     */
    public $info_hash;
    
    /**
     * The name of the file or root directory.
     * @deprecated Name is like a file, thus should go in FileInfomation
     * @var String
     */
    public $name;
    
    /**
     * Pieces
     * @var String
     */
    public $pieces;
    
    /**
     * Length of each piece.
     * @var Interger
     */
    public $piece_length;
    
    /**
     * The Pieces that you have and don't have in the form of bits.
     * 1 = have, 0 = don't have.
     *
     * @var BitArray
     */
    public $bitfield;
    
    /**
     * Pieces
     * @var bool
     */
    public $private;
    
    /* ----- Other Torrent Info ----- */
    
    /**
     * Created By
     * @var String
     */
    public $created_by;
    
    /**
     * Torrent Comment
     * @var String
     */
    public $comment;
    
    /* ----- Announce Info ----- */
    
    /**
     * Torrent Comment
     * @var AnnounceInformationList
     */
    public $announce_infos;
    
    /* ----- File Info ----- */
    
    /**
     * 
     * @var FileInfomationList
     */
    public $files;

    /* ----- Storage ----- */
    
    /**
     * The physical location of the data files 
     * @var String
     */
    public $destination;
    
    /* ----- Statistics ----- */
    
    
    public $active;
    public $bytes_left;
    public $bytes_uploaded = 0;
    public $bytes_downloaded = 0;
    
    public $last_uploaded_time;
    public $last_downloadedtime;
    
    
}











