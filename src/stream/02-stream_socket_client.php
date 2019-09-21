<?php

echo '.';

$stream = stream_socket_client('tcp://localhost:8080', $err, $errstr, 10);

echo '.';

fwrite($stream, "GET /?sleep=1 HTTP/1.0\r\n\r\n");

echo '.';

echo stream_get_contents($stream);
