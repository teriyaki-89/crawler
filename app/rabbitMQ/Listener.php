<?php
require_once( __DIR__ . '/../vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
ob_start();
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
if (!$connection) {
    die("no connection");
}
$channel = $connection->channel();

$channel->queue_declare('TendersTime', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($pg) {
     echo ' [x] Received ', $pg->body, "\n";
     $page = $pg->body;
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'http://localhost:5555/tenders/manage_queue/'.$page.'',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0'
    ));
    // Send the request & save response to $resp
    $resp = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);
     echo "page:".$page,"\n";
     if (!$resp){
         die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
     }
};
$channel->basic_consume('TendersTime', '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}
ob_end_flush();
