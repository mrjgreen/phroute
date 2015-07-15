<?php

include __DIR__ . '/../vendor/autoload.php';

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;

$collector = new RouteCollector();

$collector->filter('auth', function(){
    return "Nope!";
});

$collector->group(array('prefix' => 'admin'), function(RouteCollector $collector){

    $collector->group(['before' => 'auth'], function(RouteCollector $collector){
        $collector->get('pages', function(){
            return 'page management';
        });

        $collector->get('products', function(){
            return 'product management';
        });
    });

    // Not inside auth group
    $collector->get('orders', function(){
        return 'Order management';
    });
});

$dispatcher =  new Dispatcher($collector->getData());

echo $dispatcher->dispatch('GET', '/admin/pages'), "\n"; // Nope!
echo $dispatcher->dispatch('GET', '/admin/products'), "\n"; // Nope!
echo $dispatcher->dispatch('GET', '/admin/orders'), "\n"; // order management
