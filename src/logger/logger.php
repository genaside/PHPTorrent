<?php



/**
 * Genaeric Logger
 */
class Logger{
    // Message types
    const STATUS = 'Status';
    const CRITICAL = 'Critical'; // exit program
    const WARNING = 'Warning';
    
    /**
     * @var 
     */
    //private $program_name;
    
    /**
     * Constructor
     * @param $program_name
     */
    //public function __construct( $program_name = 'LibTorrentPHP' ){       
    //    $this->program_name = $program_name;
        
    //}

    /**
     * 
     */
    public static function logMessage( $program_name, $type, $message ){
        $datatime = date( 'Y-m-d H:i:s' );        
        
        $message_log = "[$datatime][$program_name][$type]: $message \n";
        
        if( Config::ENABLE_LOGGING_ON_STDOUT ){
            // Output message to console
            echo $message_log;
        }
        
        //TODO CHECK file permissions
        //if( $type |  ){
            error_log( $message_log, 3, Config::LOGFILE_LOCATION );
        //}    
    }

}