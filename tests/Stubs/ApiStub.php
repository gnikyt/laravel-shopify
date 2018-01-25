<?php

namespace OhMyBrew\ShopifyApp\Test\Stubs;

use OhMyBrew\BasicShopifyAPI;

class ApiStub extends BasicShopifyAPI
{
    public function request(string $method, string $path, array $params = null)
    {
        $filePath = $this->pathToHash($method, $path);
        $responseJSON = null;
        if (file_exists($filePath)) {
            $responseJSON = json_decode(file_get_contents($filePath));
        }

        return (object) [
            'body'   => $responseJSON,
            'status' => 200,
        ];
    }

    public function requestAccessToken(string $code)
    {
        $filePath = $this->pathToHash('GET', '/admin/access_token.json');

        return json_decode(file_get_contents($filePath))->access_token;
    }

    private function pathToHash($method, $path)
    {
        $path = str_replace('/', '_', parse_url($path, PHP_URL_PATH));
        $hash = hash('sha1', strtolower($method).$path);

        return __DIR__."/../fixtures/{$hash}.json";
    }
}
