<?php

require_once dirname(__DIR__) . '/bootstrap.php';


$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) { 
    $server_key = nostriphant\NIP01\Key::generate();
    
    $blossom = new \nostriphant\Blossom\Blossom($server_key, $_ENV['BLOSSOM_DATA_DIRECTORY'], $_ENV['BLOSSOM_SERVER_URL'], new \nostriphant\Blossom\UploadConstraints(
            explode(',', $_ENV['BLOSSOM_ALLOWED_PUBKEYS']),
            $_ENV['MAX_CONTENT_LENGTH'] ?? null,
            ['video/x-msvideo', 'audio/*']
    ));
    
    
    nostriphant\Functional\Functions::iterator_walk($blossom, fn(callable $route) => $route([$r, 'addRoute']));

});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

error_log('Incoming ' . $httpMethod . ' ' . $uri);

$routeInfo = $dispatcher->dispatch($httpMethod, rawurldecode($uri));
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        error_log('Not found');
        header('HTTP/1.1 404', true);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        error_log('Method not allowed');
        header('HTTP/1.1 405', true);
        break;
    case FastRoute\Dispatcher::FOUND:
        $headers = array_filter($_SERVER, fn(string $key) => str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
       
        
        error_log('Found');
        $input = fopen('php://input', 'rb');
        $response = $routeInfo[1](new \nostriphant\Blossom\HTTP\ServerRequest($headers, array_merge($_GET, $routeInfo[2]), $input));
        
        error_log(var_export(array_diff_key($response, ['body' => null]), true));
        header('HTTP/1.1 ' . $response['status'], true);
        
        $headers = $response['headers'] ?? [];
        array_walk($headers, fn(string $value, string $header) => header($header.': ' .$value));
        
        if (isset($response['body'])) {
            print $response['body'];
        }
        exit;
}

