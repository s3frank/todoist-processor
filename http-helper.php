<?php

// Mimyc some HTTP responses.
function send404()
{
    http_response_code(404);   
    echo "<h1>404 Not Found</h1>";
    echo "The page that you have requested could not be found.";
    exit();    
}
function sendOK($title, $msg)
{
    http_response_code(200);
    if (isset($title) and isset($msg))
    {
        echo "<h1>$title</h1>";
        echo "\r\n</br>$msg";            
    }   
    exit();
}
function asyncCloseOK($title, $msg)
{
    ob_end_clean();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    ignore_user_abort(true); // optional
    ob_start();
    echo "<h1>$title</h1>";
    echo "\r\n</br>$msg";  
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();     // Strange behaviour, will not work
    flush();            // Unless both are called !
    ob_end_clean();
}

?>