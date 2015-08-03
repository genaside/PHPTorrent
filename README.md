# PHPTorrent 0.0.1 alpha

## Config

...

## Torrent Daemon.

### Starting Torrent Daemon

* Run "php start.php"

### Connecting to the daemon

If you know the IP address and port of the daemon's interface socket, 
try connection to it. Note, that the deamon has two server sockets
and you should not try to confuse them. This is how we connect to daemon
using defaults.

```
$addr = '127.0.0.1';
$port = '7423';
$socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
$status = socket_connect( $socket, $addr, $port );
```

### Authentication

Since we are using the network to connect to the deamon, not only you can 
control the daemon localy you can do it remotely as well. Since we don't
want just anybody connecting to the daemon a user/pass is required.     
Write your username and password like this user:pass and send it to the 
daemon. The daemon will return 'success' if user/pass is correct.

```
// Using the defaults
$userpass = "user:pass";
socket_write( $socket, $userpass, strlen( $userpass ) );
// Now wait for daemon to return a 'success'
if( socket_read( $socket, 7 ) == "success" ){
    echo "Congrates you're connected to the daemon\n";
}else{
    echo "Authentication Failed.\n";
}
```

### Adding a torrent

To add a torrent you need to know the the torrent's path/url 
and where on the local machine you want to store the download files.
When picking the location of the torrent, just note that you can pick 
the a .torrent file from your local computer by supplying the full path.
You can also pick a url. One last thing to consider is wether 
you want the torrent to be active or not. Making a Torrent active doesn't 
mean it will start running, consider the MAX_ACTIVE_RUNNING_TORRENTS 
option in config.php
Ok lets add a torrent

```
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
```

### Removing a torrent

To delete a torrent you have to know the 40byte hash info.
Also you have an option to delete the downloaded files as well.

```
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
```

### Shutting down the daemon

Shutting down the daemon using
the socket interface is much more cleaner.

```
$message = pack( 'C', 1 );
socket_write( $socket, $message, strlen( $message ) ); 
```

































