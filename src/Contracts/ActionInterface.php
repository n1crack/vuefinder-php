<?php

namespace Ozdemir\VueFinder\Contracts;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for action handlers
 */
interface ActionInterface
{
    /**
     * Execute the action and return a response
     * 
     * @return Response|JsonResponse
     */
    public function execute();
}

