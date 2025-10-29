<?php

namespace Ozdemir\VueFinder\Exceptions;

/**
 * Exception thrown when attempting to modify a read-only storage
 */
class ReadOnlyStorageException extends VueFinderException
{
    protected $message = 'This is a readonly storage.';
}

