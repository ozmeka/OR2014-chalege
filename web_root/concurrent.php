<?php
$hosts = array("yahoo.com", "bat.unreali.tv", "aliens.org");
$timeout = 15;
$status = array();
$sockets = array();
/* Initiate connections to all the hosts simultaneously */
foreach ($hosts as $id => $host) {
    $s = stream_socket_client("$host:80", $errno, $errstr, $timeout, 
        STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
    if ($s) {
        $sockets[$id] = $s;
        $status[$id] = "in progress";
    } else {
        $status[$id] = "failed, $errno $errstr";
    }
}
/* Now, wait for the results to come back in */
while (count($sockets)) {
    $read = $write = $sockets;
    /* This is the magic function - explained below */
    $n = stream_select($read, $write, $e = null, $timeout);
    if ($n > 0) {
        /* readable sockets either have data for us, or are failed
         * connection attempts */
        foreach ($read as $r) {
            $id = array_search($r, $sockets);
            $data = fread($r, 8192);
            if (strlen($data) == 0) {
                if ($status[$id] == "in progress") {
                    $status[$id] = "failed to connect";
                }
                fclose($r);
                unset($sockets[$id]);
            } else {
                $status[$id] .= $data;
            }
        }
        /* writeable sockets can accept an HTTP request */
        foreach ($write as $w) {
            $id = array_search($w, $sockets);
            fwrite($w, "HEAD / HTTP/1.0\\r\\nHost: "
                . $hosts[$id] .  "\\r\\n\\r\\n");
            $status[$id] = "waiting for response";
        }
    } else {
        /* timed out waiting; assume that all hosts associated
         * with $sockets are faulty */
        foreach ($sockets as $id => $s) {
            $status[$id] = "timed out " . $status[$id];
        }
        break;
    }
}
foreach ($hosts as $id => $host) {
    echo "Host: $host\\n";
    echo "Status: " . $status[$id] . "\\n\\n";
}
