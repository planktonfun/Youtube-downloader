<?php

/** 
 * How to use, open it up and cmd and put the youtube id
 * the MIME type of the video. e.g. video/mp4, video/webm, etc.
 * @author Paulo Castro <paulo.castro48@gmail.com>
 *
 **/
class YoutubeDownloader 
{
    protected $format;
    protected $ids;
    protected $proxy;
    protected $context;
    protected $info;
    protected $filename;
    protected $Interval_time;
    protected $start_time;
    protected $filesize;
    protected $bytes_per_chunk;

    /**
     * Sets Initial Settings
     *
     * @return void
     **/
    public function __construct()
    {
        $this->format = "video/mp4";
        $this->ids = array();
        $this->proxy = false;
        $this->bytes_per_chunk = 4096;
    }


    /**
     * Starts the Main Logic
     *
     * @return void
     **/
    public function start()
    {
        if( $this->CInput( 'Do you wish to use a proxy [Y/N]: ' ) ) {
            $this->proxy = true;
            $this->context = $this->getProxyIp( );
        }

        while( true ) 
        {       
            $url = $this->CInput( 'Enter  youtube video ID ( type N to close ) : ' );

            if( !$url ) {
                break;
            }
            
            $au = explode('=', $url);
            $this->ids[] = $au[ ( count( $au ) - 1 )  ];
        }

        foreach( $this->ids as $id ) 
        {
            $this->getInfofromId( $id );
            
            if( $this->info['status'] == 'fail' ) {
                print_r( $this->info );
                break;
            }

            $streams = $this->info['url_encoded_fmt_stream_map'];
            $this->filename = './Folder/video_' . round( microtime( true ) * 1000 ) . '.mp4';

            echo 'Downloading media...';
            echo "\n";

            $this->downloadStream( $streams );

        }
    }


    /**
     * @param $id Youtube id
     *
     * @return void
     **/
    protected function getInfofromId( $id )
    {
        if( $this->proxy )
            parse_str( $this->getProxyUrlContent( "http://youtube.com/get_video_info?video_id=".$id ), $info);
        else
            parse_str( file_get_contents( "http://youtube.com/get_video_info?video_id=".$id ),$info);

        $this->info = $info;
    }


    /**
     * @param $url Url to get contents
     *
     * @return Url Content string
     **/
    protected function getProxyUrlContent( $url )
    {
        if( !$content = @file_get_contents( $url, false, $this->context ) ) {
            die( 'Proxy parameters are invalid' );
        }

        return $content;
    }


    /**
     * @param $streams Stream Context resource from youtube
     *
     * @return void
     **/
    protected function downloadStream( $streams )
    {
        $streams = explode(',',$streams);

        $this->Interval_time = 10;
        $this->start_time = time();
        $this->filesize = 0;

        foreach($streams as $stream)
        {
            parse_str( $stream, $data ); 

            if(stripos($data['type'], $this->format) !== false) {
                
                $this->saveVideo( $data );

                echo 'Download finished! Check the file "' . $this->filename . '"';

                break;
            }
        }
    }


    /**
     * Saves the video file to its filename
     *
     * @return void
     **/
    protected function saveVideo( $data )
    {
        if( $this->proxy )
            $video = @fopen($data['url'].'&amp;signature='.$data['sig'],'r', false, $this->context); 
        else
            $video = @fopen($data['url'].'&amp;signature='.$data['sig'],'r'); 
        
        $file = fopen($this->filename,'w');

        while ( $chunk = fread( $video, $this->bytes_per_chunk ) ) 
        {
                fwrite( $file, $chunk );
                
                $this->filesize += strlen( $chunk );
                $total_time_taken = time() - $this->start_time;

                if ( $total_time_taken > $this->Interval_time ) {
                        $this->start_time = time();
                        
                        echo $this->filesize . " bytes \n";
                }
        }

        fclose($video);
        fclose($file);

    }


    /**
     * @return stream context from user input
     *
     **/
    protected function getProxyIp( )
    {
        $proxy_ip = $this->CInput( 'Enter proxy ip: ' );
        $proxy_port = $this->CInput( 'Enter proxy port: ' );

        return stream_context_create(
            array(
                'http' => array( 
                    'proxy' => 'tcp://' . $proxy_ip . ':' . $proxy_port, 
                ) 
            )
        );
    }


    /**
     * @return User input from CLI or false if no input
     *
     **/
    protected function CInput( $question )
    {
        echo $question;

        $input = trim( fgets( STDIN ) );

        echo "\n";

        if( $input == "" || $input == 'N' || $input == 'n' ) {
            return false;
        }

        return $input;
    }

}

?>