<?php

namespace Skore\LaravelRepositories\Traits;

trait InteractsWithController
{
    /**
     * @var string
     */
    protected $context;

    /**
     * Resolve controller context automatically from request.
     *
     * @return void
     */
    protected function resolveContext()
    {
        if (request()->acceptsJson()) {
            $this->context = self::API_CONTEXT;
        }

        $this->context = self::WEB_CONTEXT;
    }


}
