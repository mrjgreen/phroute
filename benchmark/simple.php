<?php

include __DIR__ . '/../vendor/autoload.php';

$collector = new \FastRoute\RouteCollector();

$collector->addRoute('GET', '/test', function(){
    
});

$collector->addRoute('GET', '/test2', function(){
    
});

$collector->addRoute('GET', '/test3', function(){
    
});

$collector->addRoute('GET', '/test1/{name}', function(){
    
});

$collector->addRoute('GET', '/test2/{name2}', function(){
    
});

$collector->addRoute('GET', '/test3/{name3}', function(){
    
});

$dispatcher =  new FastRoute\Dispatcher($collector);

$runTime = 10;

$time = microtime(true);

$count = 0;
$seconds = 0;
while($seconds < $runTime)
{
    $count++;
    $dispatcher->dispatch('GET', '/test2/joe');
    
    if($time + 1 < microtime(true))
    {
        $time = microtime(true);
        $seconds++;
        echo $count . ' routes dispatched per second' . "\r";
        $count = 0;
    }
}

echo PHP_EOL;
    