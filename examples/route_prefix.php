<?php

include __DIR__ . '/../vendor/autoload.php';

$collector = new Phroute\Phroute\RouteCollector();

$collector->group(array('prefix' => 'admin'), function($collector){

    $collector->get('pages', function(){
        return 'page management';
    });

    $collector->get('products', function(){
        return 'product management';
    });

    $collector->get('orders', function(){
        return 'order management';
    });
});

$dispatcher =  new Phroute\Phroute\Dispatcher($collector->getData());

echo $dispatcher->dispatch('GET', '/admin/pages'), "\n"; // page management
echo $dispatcher->dispatch('GET', '/admin/products'), "\n"; // product management
echo $dispatcher->dispatch('GET', '/admin/orders'), "\n"; // order management
