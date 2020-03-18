<?php

namespace Osiset\ShopifyApp\Test\Stubs;

use ErrorException;
use Exception;
use Osiset\BasicShopifyAPI;
use stdClass;

class Api extends BasicShopifyAPI
{
    public static $stubFiles = [];

    public static function stubResponses(array $stubFiles): void
    {
        self::$stubFiles = $stubFiles;
    }

    public function rest(string $method, string $path, array $params = null, array $headers = [], bool $sync = true): stdClass
    {
        try {
            $filename = array_shift(self::$stubFiles);
            $response = json_decode(file_get_contents(__DIR__."/../fixtures/{$filename}.json"));
        } catch (ErrorException $error) {
            throw new Exception("Missing fixture for {$method} @ {$path}, tried: '{$filename}.json'");
        }

        $errors = false;
        $exception = null;
        if (property_exists($response, 'errors')) {
            $errors = true;
            $exception = new Exception();
        }

        return (object) [
            'errors'    => $errors,
            'exception' => $exception,
            'body'      => $response,
            'status'    => 200,
        ];
    }

    public function requestAccess(string $code): stdClass
    {
        return json_decode(file_get_contents(__DIR__.'/../fixtures/access_token.json'));
    }
}
