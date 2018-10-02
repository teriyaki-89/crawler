<?php

$router = $di->getRouter();

// Define a route
$router->add(
    '/tenders/manage_queue/:int',
    [
        'controller' => 'tenders',
        'action'     => 'manage_queue',
        'page'        =>  1,
    ]
);
$router->handle();
