CREATE TABLE IF NOT EXISTS Torrents(
    -- Basic torrent information
    info_hash         TEXT  PRIMARY KEY  NOT NULL, -- A 40 byte hex string  
    name              TEXT               NOT NULL, -- Name of the torrent. If the torrent has multiple files, this is the name of the root folder
    piece_length      INT                NOT NULL, -- Size of each piece
    pieces            BLOB               NOT NULL, -- The actual hashed pieces used for validation
    is_private        INT   DEFAULT 0    NOT NULL, -- Whether the torrent is private or not. 0 = not private, 1 = private        
    -- Storage
    destination       TEXT               NOT NULL, -- Location for download file(s)
    -- Statistics           
    bytes_left        INT                NOT NULL, -- Bytes left till download completes
    bytes_uploaded    INT   DEFAULT 0    NOT NULL, -- Total bytes downloaded
    bytes_downloaded  INT   DEFAULT 0    NOT NULL, -- Total bytes uploaded 
    -- Options
    active            INT   DEFAULT 1    NOT NULL, -- Whether a torrent is alowed to run, 1 = active, 0 = inactive
    download_speed    INT   DEFAULT 0    NOT NULL, -- Maximum download speed of a torrent
    upload_speed      INT   DEFAULT 0    NOT NULL  -- Maximum upload speed of a torrent
);

CREATE TABLE IF NOT EXISTS Files(
    info_hash         TEXT               NOT NULL, -- A 40 byte hex string  
    filename          TEXT                       , -- null means that the filename is the name in torrent
    filesize          INT                NOT NULL, -- Size of file
    FOREIGN KEY( info_hash ) REFERENCES Torrents( info_hash ) ON DELETE CASCADE,
    UNIQUE( info_hash, filename )
);

CREATE TABLE IF NOT EXISTS AnnounceUrls(
    info_hash         TEXT               NOT NULL, -- A 40 byte hex string  
    url               TEXT               NOT NULL, -- Announce url
    rank              INT   DEFAULT 0    NOT NULL, -- The rank of how good the tracker is in returning results
    FOREIGN KEY( info_hash ) REFERENCES Torrents( info_hash ) ON DELETE CASCADE,
    UNIQUE( info_hash, url )
);

CREATE INDEX IF NOT EXISTS idx_1 on Torrents( info_hash );
CREATE INDEX IF NOT EXISTS idx_2 on Files( info_hash );
CREATE INDEX IF NOT EXISTS idx_3 on AnnounceUrls( info_hash );