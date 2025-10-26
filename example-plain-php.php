<?php

/**
 * Example: Simple Plain PHP Integration
 * 
 * This is the easiest way - just call actions directly!
 */

require 'vendor/autoload.php';

use Ozdemir\VueFinder\VueFinderCore;
use Ozdemir\VueFinder\Action\VueFinderActionFactory;
use Ozdemir\VueFinder\Service\FilesystemService;
use Ozdemir\VueFinder\Service\PathParser;
use Ozdemir\VueFinder\Service\StorageResolver;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// 1. Configure your storages
$storages = [
    'local' => new LocalFilesystemAdapter(__DIR__ . '/uploads'),
];

use Ozdemir\VueFinder\VueFinderBuilder;

// 2. ONE LINE SETUP! 🎉
$vueFinderCore = VueFinderBuilder::create(
    $storages,
    ['publicLinks' => ['local://public' => 'https://example.com']]
);

// 3. Create action factory
$actionFactory = new VueFinderActionFactory($vueFinderCore);

// 5. Handle the request
$request = Request::createFromGlobals();

// Simple routing - you decide the URLs!
$actionName = match (true) {
    $request->getPathInfo() === '/api/files' && $request->getMethod() === 'GET' => 'index',
    $request->getPathInfo() === '/api/files/search' && $request->getMethod() === 'GET' => 'search',
    $request->getPathInfo() === '/api/files/upload' && $request->getMethod() === 'POST' => 'upload',
    $request->getPathInfo() === '/api/files/delete' && $request->getMethod() === 'POST' => 'delete',
    default => null,
};

// Also support query parameter method for backwards compatibility
if (!$actionName) {
    $actionName = $request->query->get('q');
}

if (!$actionName) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// Execute the action
try {
    $action = $actionFactory->setRequest($request)->create($actionName);
    $response = $action->execute();
    
    // Set CORS headers
    $response->headers->set('Access-Control-Allow-Origin', "*");
    $response->headers->set('Access-Control-Allow-Headers', "*");
    
    $response->send();
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Now you can call:
 * 
 * GET  /api/files?path=local://uploads
 * GET  /api/files/search?path=local://uploads&filter=*.jpg
 * POST /api/files/upload?path=local://uploads
 * POST /api/files/delete?path=local://uploads
 * 
 * Or use query method:
 * GET  /?q=index&path=local://uploads
 * POST /?q=upload&path=local://uploads
 */

