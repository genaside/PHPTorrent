<?php



/**
 * Handles download files for the bittorent client.
 * @note The contents of the directory will be treated
 * as one continuous file.
 */
class Storage{
    
      
    
    /**
     * Create file or directory structure based on the files
     *     
     * @param $file_info_list FileInformationList
     * @param $destination path to create the structure     
     * @returns True if successful, False on failire 
     */
    public static function createStorage( FileInformationList $file_info_list, $destination ){
        // Examine destination path
        $dir_info = new SplFileInfo( $destination );
        
        if( !$dir_info->isDir() ){
            echo "$destination is not a directory\n";     
            return false;
        }       
        if( !( $dir_info->isReadable() && $dir_info->isWritable() ) ){
            echo "read/write access denied to $destination\n";
            return false;
        }
        
        // Create files
        foreach( $file_info_list as $file_info ){
            $full_path = $destination . DIRECTORY_SEPARATOR . $file_info->name;
            $dir = dirname( $full_path );
            if( !file_exists( $dir ) ){
                mkdir( $dir, 0755, true );
            }
            
            // fill file with zeros
            $file_handle = fopen( $full_path, "wb" );
            fseek( $file_handle, $file_info->size - 1, SEEK_CUR );
            fwrite( $file_handle, "a" );
            fclose( $file_handle );
        }        
        return true;
    }
    
    public static function deleteStorage( TorrentInformation $torrent_info ){
        foreach( $torrent_info->files as $file ){
            $full_path = $torrent_info->destination . DIRECTORY_SEPARATOR . $file->name;
            if( !file_exists( $full_path ) ){
                continue;
            }      
            unlink( $full_path );
        }
        if( file_exists( $full_path = $torrent_info->destination . DIRECTORY_SEPARATOR . $torrent_info->name ) ){
             unlink( $full_path );
        }  
    }
    
    //____________
    
    /**
     * Examine the file(s) to determine next avalable piece.
     * The piece in the torrent will be matched to the piece
     * of data in the file.
     *
     * @param $db_torrent is torrent information in the database
     * and not from the torrent file its self.
     *
     * @deprecated
     * @returns The index of peice not present in the file
     * or false if we have all the piecies matched up.
     */
    public static function getNextAvailablePiece( $db_torrent, $skip = 0 ){
        // split the combined pieces into 20bytes each
        $pieces = str_split( $db_torrent[ 'pieces' ], 20 );
        $piece_length = $db_torrent[ 'piece_length' ];  
        // The size of all files combined
        $data_length = $db_torrent[ 'length' ]; 
        
        $number_of_pieces = ceil( $data_length / $piece_length );     
                
        for( $i = $skip; $i < $number_of_pieces; ++$i ){
            $seek = $i * $piece_length;            
            $block = self::read( $db_torrent, $seek, $piece_length );
            
            if( sha1( $block, true ) != $pieces[ $i ] ){                  
                return $i;
            }
        }        
        return false;
    }
    
        
    
    
    /**
     * Full hash check
     * @return A binary string repersenting the 
     * @return A BitArray Object
     * pieces we have and dont have(1s and 0s).
     */
    public static function fullHashCheck( TorrentInformation $torrent_info ){
    
        // Seperate the 20byte hashes
        $pieces = str_split( $torrent_info->pieces, 20 );
        $piece_length = $torrent_info->piece_length;                    
             
        $number_of_pieces = count( $pieces ); 
                
        $bitfield = ''; 
        $byte = 0;        
        for( $i = 0; $i < $number_of_pieces; ++$i ){            
            $seek = $i * $piece_length;            
            $block = self::read( $torrent_info, $seek, $piece_length );
            
            if( sha1( $block, true ) == $pieces[ $i ] ){   
                // The data in the file matches the piece in the torrent
                // Remember to start on the High bit
                // Turn on a bit in this byte
                $byte = $byte | 128 >> ( $i % 8 );                
            }
            
            if( ( $i % 8 ) == 7 || $i == $number_of_pieces - 1 ){
                // byte is filled or near then end of the array, store byte in bitfield.
                $bitfield .= pack( 'C', $byte ); 
                $byte = 0;                
            }
        }  
        
        return $bitfield; 
    }
    
    
    /**
     * Read
     *
     */
    public static function read( TorrentInformation $torrent_info, $seek, $length ){
    
        $hasleft = $length;
        $buffer = ''; 
        
        foreach( $torrent_info->files as $file ){
            $full_path = $torrent_info->destination . DIRECTORY_SEPARATOR . $file->name;
            if( !file_exists( $full_path ) ){
                throw new Exception( "file does not exist." );
            }            
            
            if( $seek >= $file->size ){
                // Seek is greater than or equal the size of this file, so adjust seek and goto next file               
              $seek -= $file->size;  
              continue;
            }else{
                // the file supports the current size of seek               
            }   
                                  
            $file_handle = fopen( $full_path, "rb" );            
            fseek( $file_handle, $seek, SEEK_SET );
            
            if( ( $diff = $file->size - $seek ) < $hasleft ){ 
                // If the read length passes the filesize, read up into the filesize
                // then goto the next file to read the rest
                $buffer .= fread( $file_handle , $diff );                
                $seek = 0; // I dont need this anymore for this loop                
                $hasleft -= $diff;                
            }else{
                // The whole read length fits well in this file
                $buffer .= fread( $file_handle , $hasleft );
                fclose( $file_handle );                
                break; // all done
            }           
            fclose( $file_handle );            
        }
        return $buffer;
    }
    
    /**
     * Read
     *
     */
    public static function write( $torrent_info, $seek, $data ){  
        $hasleft = strlen( $data ); // how much left to write       
        
        foreach( $torrent_info->files as $file ){
            $full_path = $torrent_info->destination . DIRECTORY_SEPARATOR . $file->name;
            if( !file_exists( $full_path ) ){
                throw new Exception( "file does not exist." );
            }            
            
            if( $seek >= $file->size ){
                // Seek is greater than or equal the size of this file, so adjust seek and goto next file               
              $seek -= $file->size;  
              continue;
            }else{
                // the file supports the current size of seek               
            }   
                                  
            $file_handle = fopen( $full_path, "rb+" );            
            fseek( $file_handle, $seek, SEEK_SET );
            
            if( ( $diff = $file->size - $seek ) < $hasleft ){ 
                // If the write length passes the filesize, write up into the filesize
                // then goto the next file to write the rest
                fwrite( $file_handle, $data, $diff );
                $data = substr( $data, $diff );
                $seek = 0; // I dont need this anymore for this loop                
                $hasleft -= $diff;                
            }else{
                // The whole write length fits well in this file
                fwrite( $file_handle, $data );                
                fclose( $file_handle );                
                return; // all done
            }           
            fclose( $file_handle );            
        }           
    }
    
    
    
    
   
    
    

    
    
    
    
    
    
    
    
    
    


}