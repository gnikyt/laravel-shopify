<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\BasicShopifyAPI;

class ApiStub extends BasicShopifyAPI
{
    public function request(string $method, string $path, array $params = null)
    {
        $path = str_replace('/', '_', parse_url($path, PHP_URL_PATH));
        $filePath = __DIR__.'/fixtures/'.strtolower($method).$path;

        $responseJSON = null;
        if (file_exists($filePath)) {
            $responseJSON = json_decode(file_get_contents($filePath));
        }

        return (object) [
            'body' => $responseJSON,
            'status' => 200
        ];
    }

    public function requestAccessToken(string $code)
    {
        $filePath = __DIR__.'/fixtures/post_admin_access_token.json';
        return json_decode(file_get_contents($filePath))->access_token;
    }
}
