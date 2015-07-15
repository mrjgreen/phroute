<?php

include __DIR__ . '/../vendor/autoload.php';

$collector = new Phroute\Phroute\RouteCollector();

$collector->get('/', function(){
    return 'Home Page';
});

$collector->post('products', function(){
    return 'Create Product';
});

$collector->put('items/{id}', function($id){
    return 'Amend Item ' . $id;
});

$dispatcher =  new Phroute\Phroute\Dispatcher($collector->getData());

echo $dispatcher->dispatch('GET', '/'), "\n";   // Home Page
echo $dispatcher->dispatch('POST', '/products'), "\n"; // Create Product
echo $dispatcher->dispatch('PUT', '/items/123'), "\n"; // Amend Item 123
