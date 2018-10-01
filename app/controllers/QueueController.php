<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);


$queue = new Phalcon\Queue\Beanstalk(array('host' => '127.0.0.1', 'port' => '11300'));
if (!$queue) {
    die ('error to connect');
} else {
    while (($job = $queue->reserve()) !== false) {
        $message = $job->getBody();
        print_r($message['page']);
        $page = $message['page'];
        header('Location:http://localhost:5555/tenders/manage_queue/'.$page.'');
        $job->delete();
    }
}






