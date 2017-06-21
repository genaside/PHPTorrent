<?php



/**
 * Genaeric Logger
 */
class Logger{
    

    // Message types
    const STATUS = 1;    
    const CRITICAL = 2; // exit program
    const WARNING = 3;
    const DEBUG = 4;
    
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
        if( $type == self::STATUS ){ $type_str = 'Status'; }
        else if( $type == self::CRITICAL ){ $type_str = 'Critical'; }
        else if( $type == self::WARNING ){ $type_str = 'Warning'; }
        else if( $type == self::DEBUG ){ $type_str = 'Debug'; }
        
        
        $message_log = "[$datatime][$program_name][$type_str]: $message\n";
        
        $report = function() use( &$message_log ){
            if( Config::ENABLE_LOGGING_ON_STDOUT ){
                // Output message to console
                echo $message_log;
            }
            //
            error_log( $message_log, 3, Config::LOGFILE_LOCATION );                        
        };
        
        
        
        
        //TODO CHECK file permissions
        
        if( Config::LOG_LEVEL == 1 ){
            switch( $type ){
                case self::STATUS:
                case self::CRITICAL:
                case self::WARNING:
                    $report();
                    break;
                default:
                    break;                
            }            
        }else if( Config::LOG_LEVEL == 2 ){
            switch( $type ){
                case self::STATUS:
                case self::CRITICAL:
                case self::WARNING:
                case self::DEBUG:
                    $report();
                    break;
                default:
                    break;                
            }  
        }    
        
        
    }
    
    
    
    

}