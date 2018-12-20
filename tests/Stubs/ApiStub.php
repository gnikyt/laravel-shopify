<?php

namespace OhMyBrew\ShopifyApp\Test\Stubs;

use ErrorException;
use Exception;
use OhMyBrew\BasicShopifyAPI;

class ApiStub extends BasicShopifyAPI
{
    public static $stubFiles = [];

    public static function stubResponses(array $stubFiles)
    {
        self::$stubFiles = $stubFiles;
    }

    public function rest(string $method, string $path, array $params = null)
    {
        try {
            $filename = array_shift(self::$stubFiles);
            $response = json_decode(file_get_contents(__DIR__."/../fixtures/{$filename}.json"));
        } catch (ErrorException $error) {
            throw new Exception("Missing fixture for {$method} @ {$path}, tried: '{$filename}.json'");
        }

        return (object) [
            'body'   => $response,
            'status' => 200,
        ];
    }

    public function requestAccessToken(string $code)
    {
        return json_decode(file_get_contents(__DIR__.'/../fixtures/access_token.json'))->access_token;
    }
}
