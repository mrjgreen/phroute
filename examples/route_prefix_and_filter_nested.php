<?php

include __DIR__ . '/../vendor/autoload.php';

$collector = new Phroute\Phroute\RouteCollector();

$collector->filter('auth', function(){
    return "Nope!";
});

$collector->group(array('prefix' => 'admin'), function($collector){

    $collector->group(['before' => 'auth'], function($collector){
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

$dispatcher =  new Phroute\Phroute\Dispatcher($collector->getData());

echo $dispatcher->dispatch('GET', '/admin/pages'), "\n"; // Nope!
echo $dispatcher->dispatch('GET', '/admin/products'), "\n"; // Nope!
echo $dispatcher->dispatch('GET', '/admin/orders'), "\n"; // order management
