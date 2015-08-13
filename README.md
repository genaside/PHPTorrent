# PHPTracker 0.0.03 alpha

## About

This is a BitTorrent client based on the PHP(5.4) language.
The project has a lot of work to be done on it; however, for
simple leeching/seeding it should work. I am interested in any
problems you may have with the program.

Check out tcz/PHPTracker for all your tracker needs.

========

## Config

For now the only options you should be concerned about is the 
CLIENT_DATABASE_LOCATION and LOGFILE_LOCATION config options in the config/config.php.
Set these options to your perfered locatations.

You can tweek the rest of the config if you want.

...

## Torrent Daemon.

In v0.0.03 you can now use interface.php found in ./utils/api/ to control the daemon more easily.
Take the interface.php and put it anywhere you want. Also note that defaults are being used for
these examples.

### Starting Torrent Daemon

The start the PHPTorrent goto ./src/ to find start.php and run it.

* Run "php start.php"

### Connecting to the daemon

Before you can use any of the interface controls you should connect first.
If you know the IP address and port of the daemon's interface socket, 
try connection to it. Note, that the deamon has two server sockets
and you should not try to confuse them;however, the port should be 
7423 when using the defaults. This is how we connect to daemon.

```
$controls = new PHPTorrentInterface();
$controls->connect( 7423, '127.0.0.1' );
```

### Connecting to the daemon with authentication

Since we are using the network to connect to the daemon, not only you can 
control the daemon localy you can do it remotely as well. If we don't
want just anybody connecting to the daemon, a user/pass is required.     
The daemon will return 1(true) if connection is successfull AND 
user/pass is correct. By default the username isnt set, 
which disables the authentication process. If you want
to use user/pass, then define them in the config. Here is how
you connect with username and password, 

```
$controls = new PHPTorrentInterface();
$success = $controls->connect( 7423, '127.0.0.1', 'user', 'pass' );
if( !$success ){
    echo "connection failed check logs.\n";
}
```

### Adding a torrent

To add a torrent you need to know the torrent's path/url 
and where on your local machine you want to store the download files.
When picking the location of the torrent, just note that you can pick 
a local source or URL. Magnet links or not yet supported. The last thing 
to know is if you want to make the torrent active or not. The activity of 
the torrent determinds if it should be running(seeding/leaching).
Making a Torrent active doesn't mean it will start running, 
consider the MAX_ACTIVE_RUNNING_TORRENTS option in config.php.
Ok lets add a torrent

```
// create special object 
$form = new AddTorrentForm();
// selecting a URL source of the torrent(not magnet)
$form->torrent_source_path = "http://linuxtracker.org/download.php?id=7063785beefd3a9816d3a279242d80f5a27a5390&f=kali_linux_1.1.0a_amd64_mini.torrent&key=6c2d037a";
// selecting the download destination
$form->download_destination = "/home/user/Downloads/";
// making the torrent active
$form->active = true;

// Now we can send the data
$controls->addTorrent( $form );
```

### Removing a torrent

After you connect, you can delete a torrent you just have to
know the 40byte hash info. Also you have an option 
to delete the downloaded files as well.

```
// delete torrent from database and all downloaded files associated with it
$controls->removeTorrent( "7063785beefd3a9816d3a279242d80f5a27a5390", true );
```

### Make the torrent active

Make a torrent active.

```
$controls->activateTorrent( "7063785beefd3a9816d3a279242d80f5a27a5390" );
```

### Make the torrent inactive

Deactivate a torrent.

```
$controls->deactivateTorrent( "7063785beefd3a9816d3a279242d80f5a27a5390" );
```

### Show all currently running torrent

Show only currently running torrents in json format.

```
$data = $test->displayAllRunningTorrent();
var_dump( $data );
```

### Show All torrent

Show all the torrents in the database in json format.

```
$data = $test->displayAllTorrent();
var_dump( $data );
```

### Shutting down the daemon

Shutting down the daemon cleanly.

```
$controls->shutdownDaemon();
```

### Disconnect from Daemon

Close the connect between you and the daemon.

```
$controls->disconnect();
```


















