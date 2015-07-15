<?php

include __DIR__ . '/../vendor/autoload.php';

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;

$collector = new RouteCollector();

$USER_SESSION = false;

$collector->filter('auth', function() use(&$USER_SESSION){
    if(!$USER_SESSION)
    {
        return "Nope! Must be authenticated";
    }
});

$collector->group(array('before' => 'auth'), function(RouteCollector $collector){

    $collector->get('/', function(){
        return 'Hurrah! Home Page';
    });
});

$dispatcher =  new Dispatcher($collector->getData());

echo $dispatcher->dispatch('GET', '/'), "\n"; // Nope! Must be authenticated

$USER_SESSION = true;

echo $dispatcher->dispatch('GET', '/'), "\n"; // Hurrah! Home Page
