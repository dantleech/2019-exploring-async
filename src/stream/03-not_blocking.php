<?php

$stream = stream_socket_client('tcp://localhost:8080');

stream_set_blocking($stream, false);

fwrite($stream, "GET /?sleep=1 HTTP/1.0\r\n\r\n");

$write = null;
$except = null;

$read = [ $stream ];


stream_select($read, $write, $except, null);

echo stream_get_contents($read[0]);

