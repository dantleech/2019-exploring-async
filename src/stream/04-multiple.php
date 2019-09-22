<?php

$stream1 = stream_socket_client('tcp://localhost:8080');
stream_set_blocking($stream1, false);
fwrite($stream1, "GET /?sleep=1 HTTP/1.0\r\n\r\n");

$stream2 = stream_socket_client('tcp://localhost:8080');
stream_set_blocking($stream2, false);
fwrite($stream2, "GET /?sleep=1 HTTP/1.0\r\n\r\n");

$await = [ $stream1, $stream2 ];

$write = $except = null;

while ($await) {
    $read = $await;
    $write = $except = null;

    // waits here!
    stream_select($read, $write, $except, null);

    foreach ($read as $stream) {
        echo stream_get_contents($stream) . PHP_EOL;

        unset($await[array_search($stream, $await)]);
        fclose($stream);
    }
}
