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
$router->add(
    '/tenders/getPagi/pPage=([0-9]{1,5})(/search=)?([^*]{1,25})?',
    [
        'controller' => 'tenders',
        'action'     => 'getPagi',
        'perPage'    =>  1,
        'search'     =>  3,

    ]
);
$router->add(
    '/tenders/showData/pPage=([0-9]{1,5})/page=([0-9]{1,8})(/search=)?([^*]{1,25})?',
    [
        'controller' => 'tenders',
        'action'     => 'showData',
        'perPage'    =>  1,
        'page'       =>  2,
        'search'     =>  4,
    ]
);
$router->handle();
