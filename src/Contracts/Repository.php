<?php

namespace Skore\LaravelRepositories\Contracts;

interface Repository
{
    /**
     * Get web context string value.
     */
    public const WEB_CONTEXT = 'web';

    /**
     * Get api context string value.
     */
    public const API_CONTEXT = 'api';
}
