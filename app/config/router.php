<?php

$router = $di->getRouter();

// Define a route
$router->add(
    '/tenders/download',
    [
        'controller' => 'tenders',
        'action'     => 'download',
    ]
);

$router->add(
    '/tenders/manage_queue/([0-9]{1-5})',
    [
        'controller'     => 'tenders',
        'action'         => 'test',
        'page'        =>  1,
    ]
);

$router->handle();
