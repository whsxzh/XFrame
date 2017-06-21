<?
function readf($filename)
{       if(file_exists($filename))
{
     if($fd = fopen( $filename, "r" ))
     {
     $contents = fread($fd, filesize($filename));
     fclose( $fd );
     return trim($contents);
     }  }
     else
         return "";
}

function writef($newfile,$filename)
{
   $fd=fopen($filename, "w" );
   fwrite($fd,$newfile);
   fclose($fd);
}

function appendf ($string,$filename)
{
   $fd=fopen($filename, "a" );
   fwrite($fd,$string);
   fclose($fd);
}

function delf($filename)
{
 if(file_exists($filename))
    unlink($filename) ;
}


?>