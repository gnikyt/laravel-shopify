<?php

namespace Osiset\ShopifyApp\Exceptions;

/**
 * Exception for use in requests that need http responses.
 */
class HttpException extends BaseException
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
            ], $this->getCode());
        }

        return response($this->getMessage(), $this->getCode());
    }
}
