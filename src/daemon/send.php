<?php

/*
    Here is a quick tutorial on controlling PHPTorrent while it's running.
    Make sure PHPTorrent/daemon is running on command line and not on apache.
    You can also look at config.php to change various options.
*/

/*
    --- Connecting to the daemon ---
    If you know the ip address and port of the daemon's interface socket, 
    try connection to it. Note, that the deamon has two server sockets
    and you should not try to confuse them. This is how we connect to daemon
    using defaults.
*/
$addr = '127.0.0.1';
$port = '7423';
$socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
$status = socket_connect( $socket, $addr, $port );

/*
    --- Authentication ---
    Since we are using the network to connect to the deamon, not only you can 
    control the daemon localy you can do it remotely as well. Since we don't
    want just anybody connection to the daemon a user/pass is required.     
    Write your username and password like this user:pass and send it to the 
    daemon. The daemon will return 'success' if user/pass is correct.
*/
// Using the defaults
$userpass = "user:pass";
socket_write( $socket, $userpass, strlen( $userpass ) );
// Now wait for daemon to return a success
if( socket_read( $socket, 7 ) == "success" ){
    echo "Congrates you're connected to the daemon\n";
}else{
    echo "Authentication Failed.\n";
}

// We can do alot of things at this point. 


//goto delete_torrent_example;
goto add_torrent_example;
//goto activate_torrent_example;
//goto deactivate_torrent_example;
//goto show_all_running_torrents;
//goto show_all_torrents;

add_torrent_example:
/*
    --- Adding a torrent ---
    To add a torrent you need to know the the torrent's path/url 
    and where on the local machine you want to store the download files.
    When picking the location of the torrent, just note that you can pick 
    the a .torrent file from your local computer by supplying the full path.
    You can also pick a url. One last thing to consider is wether 
    you want the torrent to be active or not. Making a Torrent active doesn't 
    mean it will start running, consider the MAX_ACTIVE_RUNNING_TORRENTS 
    option in config.php
    Ok lets add a torrent
*/
$torrent_source = "http://torcache.net/torrent/D1A4C166759C81886B88D227732F5952FB679610.torrent?title=[kat.cr]animerg.dragon.ball.super.001.720p.phr0sty.mkv";
$download_destination = "/home/god/Downloads/";
$active = false;

// pack it nicely for socket
$message = pack( 'C', 50 ); // operation 50 means add torrent from source
$message .= pack( 'N', strlen( $torrent_source ) ) . $torrent_source;
$message .= pack( 'N', strlen( $download_destination ) ) . $download_destination;
$message .= pack( 'C', $active );   
// send it
socket_write( $socket, $message, strlen( $message ) ); 

// Now wait for the reponse
if( ( $reponse = socket_read( $socket, 1024 ) ) == "success" ){
    echo "Congrates torrent has been added\n";
}else{
    echo $reponse; // Got an error
}

goto exit_;
delete_torrent_example:
/*
    --- Delete a torrent. ---
    To delete a torrent you have to know the 40byte hash info.
    Also you have an option to delete the downloaded files.
*/
$info_hash = "0c00ed0f04dc62faa2b1c67749a44590338a7cf4";
$delete_files = true;

$message = pack( 'C', 51 );
$message .= $info_hash;
$message .= pack( 'C', $delete_files );
socket_write( $socket, $message, strlen( $message ) ); 

// Now wait for the reponse
if( ( $reponse = socket_read( $socket, 1024 ) ) == "success" ){
    echo "Congrates torrent has been removed\n";
}else{
    echo $reponse; // Got an error
}

goto exit_;
activate_torrent_example:

/*
    --- Active Torrent ---
    Make a torrent active and if the running torrents is less than
    MAX_ACTIVE_RUNNING_TORRENTS , the torrent will start running.
*/
$info_hash = "0c00ed0f04dc62faa2b1c67749a44590338a7cf4";
$message = pack( 'C', 56 );
$message .= $info_hash;
socket_write( $socket, $message, strlen( $message ) ); 

goto exit_;
deactivate_torrent_example:

/*
    --- Deactivate Torrent ---
    Make a torrent inactive
*/
$info_hash = "0c00ed0f04dc62faa2b1c67749a44590338a7cf4";
$message = pack( 'C', 57 );
$message .= $info_hash;
socket_write( $socket, $message, strlen( $message ) ); 

goto exit_;
show_all_running_torrents:

/*
   --- Show all running torrents ---
   Show all of the are currently running.
   Note, that the response will be in json format
*/
$message = pack( 'C', 75 );
socket_write( $socket, $message, strlen( $message ) ); 
$reponse = socket_read( $socket, 4096 );
// It will be up to you on how to use this data
$obj = json_decode( $reponse );
var_dump( $obj );

goto exit_;
show_all_torrents:
/*
   --- Show all torrents ---   
   Shows all torrents in the database
*/
$message = pack( 'C', 76 );
socket_write( $socket, $message, strlen( $message ) ); 
$reponse = socket_read( $socket, 8192 ); // read alot more
// It will be up to you on how to use this data
$obj = json_decode( $reponse );
var_dump( $obj );

goto exit_;

exit_:
socket_close( $socket );
exit();
$handle = fopen( "php://stdin","r" );

/*
printf( "Enter IP Address: " );
$addr = trim( fgets( $handle ) );
printf( "Enter IP Port: " );
$port = trim( fgets( $handle ) );
printf( "Connecting...\n" );
*/




$repeat = true;
while( $repeat ){
    $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
    $status = socket_connect( $socket, $addr, $port );
    if( !$status ){
        exit();
    }

    printOptions();
    printf( "Enter Option: " );
    $option = trim( fgets( $handle ) );    
    
    switch( $option ){
        case '0':
            $message = pack( 'C', 0 );      
            socket_write( $socket, $message, strlen( $message ) );
            break;
        case '1':
            printf( "Enter path of torrent file: " );
            $torrent_source_path = trim( fgets( $handle ) );    
            
            printf( "Enter download destination : " );
            $download_destination = trim( fgets( $handle ) );
        
            $message = pack( 'C', 50 );
            $message .= pack( 'N', strlen( $torrent_source_path ) ) . $torrent_source_path;
            $message .= pack( 'N', strlen( $download_destination ) ) . $download_destination;
            
            printf( "Start Torrent(make active)? (y/n): " );
            $active = trim( fgets( $handle ) );
            if( $active == 'y' ){
                $active = 1;
            }else{
                $active = 0;
            }
            
            $message .= pack( 'C', $active );            
            
            socket_write( $socket, $message, strlen( $message ) );
            break;
        case '9':
            $message = pack( 'C', 75 );      
            socket_write( $socket, $message, strlen( $message ) );
            $json_data = socket_read( $socket, 2048 );
            $data = json_decode( $json_data );
            displayTorrents( $data );
            break;
        case 'q':
            $repeat = false;
            break;
    }
    printf( "Sent message to daemon\n" );
    socket_close( $socket );
}



exit();



function printOptions(){
    $mask = "| %-7s | %-30.30s\n";    
    printf( $mask, 'Options', 'Description' );
    printf( "------------------------------------------------------------\n" );
    printf( $mask, '0', 'Shutdown Daemon' );
    printf( $mask, '1', 'Add torrent from file.' );
    printf( $mask, '2', 'Remove Torrent.' );
    printf( $mask, '3', 'Toggle torrent active status.' );
    printf( $mask, '8', 'Display active torrents.' );
    printf( $mask, '9', 'Display all torrents.' );
    printf( $mask, 'q', 'Quit this script.' );
}

function displayTorrents( $data ){
    $mask = "| %-40s | %-20.20s | %-10s | %-8s | %-8s \n";    
    printf( $mask, 'Info Hash', 'Name', 'Downloaded', 'Uploaded', 'left', '' );
    printf( "------------------------------------------------------------\n" );
    foreach( $data as $item ){        
        printf( $mask, $item->info_hash, $item->name, $item->bytes_downloaded, $item->bytes_uploaded, $item->bytes_left );
    }
    printf( "---------------------------END------------------------------\n\n" );
}







// Add torrent {code}{length}{source_torrent}{length}{destination}
$message = pack( 'C', 50 );
//$torrent_source_path = "/run/media/god/Taws/prepared_data/torrents/dumps.torrent";
//$torrent_source_path = "/run/media/god/Taws/prepared_data/torrents/2015052814700-dumps.torrent";
$torrent_source_path = "/home/god/Desktop/db_encyclopedia.tar.gz.torrent";
$message .= pack( 'N', strlen( $torrent_source_path ) ) . $torrent_source_path;
$download_destination = "/home/god/Downloads/";
$message .= pack( 'N', strlen( $download_destination ) ) . $download_destination;
socket_write( $socket, $message, strlen( $message ) );
socket_close( $socket );

//socket_write( $socket, pack( 'C', 50 ), 1 );  7423


//$path = "/run/media/god/Taws/prepared_data/torrents/dumps.torrent";
//$message = pack( 'CN', 50, strlen( $path ) ) . $path;


// request a piece
//$message = pack( 'NCNNN', 13, 6, $piece_index, 0, $this->torrent_data[ 'info' ][ 'piece length' ]  );
//socket_write( $socket, $message, strlen( $message ) );