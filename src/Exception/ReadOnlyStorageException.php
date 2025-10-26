<?php

namespace Ozdemir\VueFinder\Exception;

/**
 * Exception thrown when attempting to modify a read-only storage
 */
class ReadOnlyStorageException extends VueFinderException
{
    protected $message = 'This is a readonly storage.';
}

