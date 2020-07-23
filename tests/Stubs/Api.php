<?php

namespace Osiset\ShopifyApp\Test\Stubs;

use stdClass;
use Exception;
use ErrorException;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;

class Api extends BasicShopifyAPI
{
    public static $stubFiles = [];

    public static function stubResponses(array $stubFiles): void
    {
        self::$stubFiles = $stubFiles;
    }

    public function rest(string $method, string $path, array $params = null, array $headers = [], bool $sync = true): array
    {
        try {
            $filename = array_shift(self::$stubFiles);
            $response = json_decode(file_get_contents(__DIR__."/../fixtures/{$filename}.json"), true);
        } catch (ErrorException $error) {
            throw new Exception("Missing fixture for {$method} @ {$path}, tried: '{$filename}.json'");
        }

        $errors = false;
        $exception = null;
        if (isset($response['errors'])) {
            $errors = true;
            $exception = new Exception();
        }

        return [
            'errors'     => $errors,
            'exception'  => $exception,
            'body'       => new ResponseAccess($response),
            'status'     => 200,
        ];
    }

    public function graph(string $query, array $variables = [], bool $sync = true): array
    {
        try {
            $filename = array_shift(self::$stubFiles);
            $response = json_decode(file_get_contents(__DIR__."/../fixtures/{$filename}.json"), true);
        } catch (ErrorException $error) {
            throw new Exception('Missing fixture for GraphQL call');
        }

        $errors = false;
        $exception = null;
        if (isset($response['errors'])) {
            $errors = $response['errors'];
            $exception = new Exception();
        }

        return [
            'errors'     => $errors,
            'exception'  => $exception,
            'response'   => $response,
            'status'     => 200,
            'body'       => new ResponseAccess($response),
        ];
    }

    public function requestAccess(string $code): ResponseAccess
    {
        return new ResponseAccess(
            json_decode(file_get_contents(__DIR__.'/../fixtures/access_token.json'), true)
        );
    }
}
