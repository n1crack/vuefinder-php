<?php

namespace Ozdemir\VueFinder\Exceptions;

/**
 * Exception thrown when an invalid HTTP method is used for an action
 */
class InvalidMethodException extends VueFinderException
{
    protected $message = 'The query does not have a valid method.';
}

