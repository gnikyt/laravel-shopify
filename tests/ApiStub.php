<?php namespace OhMyBrew\ShopifyApp\Test;

class ApiStub
{
    public function request(string $method, string $endpoint, array $params = [])
    {
        $path = str_replace('/', '_', parse_url($endpoint, PHP_URL_PATH));
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
}
