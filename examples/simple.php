<?php

include __DIR__ . '/../vendor/autoload.php';

$collector = new Phroute\Phroute\RouteCollector();

$collector->get('/', function(){
    return 'Home Page';
});

$collector->post('products', function(){
    return 'Create Product';
});

$collector->put('orders/{id}', function($id){
    return 'Update Order ' . $id;
});

$dispatcher =  new Phroute\Phroute\Dispatcher($collector->getData());

echo $dispatcher->dispatch('GET', '/'), "\n";   // Home Page
echo $dispatcher->dispatch('POST', '/products'), "\n"; // Create Product
echo $dispatcher->dispatch('PUT', '/orders/123'), "\n"; // Update Order 123
