<?php

namespace DachcomDigital\SocialDataFacebook\Client;

use League\OAuth2\Client\Provider\Facebook;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FacebookClient
{
    protected const GRAPH_API_VERSION = 'v12.0';
    
    protected HttpClientInterface $client;
    protected array $configuration;
    
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->client = HttpClient::create([
            'base_uri' => sprintf('https://graph.facebook.com/%s/', self::GRAPH_API_VERSION),
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
    }
    
    public function getOauthProvider(string $redirectUri = ''): Facebook
    {
        return new Facebook([
            'clientId' => $this->configuration['app_id'],
            'clientSecret' => $this->configuration['app_secret'],
            'redirectUri' => $redirectUri,
            'graphApiVersion' => self::GRAPH_API_VERSION
        ]);
    }
    
    public function get(string $endpoint, array $params = []): array
    {
        $endpoint = ltrim($endpoint, '/');
        $params = array_merge($params, [
            'access_token' => $this->configuration['access_token'],
            'appsecret_proof' => hash_hmac('sha256', $this->configuration['access_token'], $this->configuration['app_secret'])
        ]);
        $response = $this->client->request('GET', $endpoint, ['query' => $params]);
        return $this->parseResponseJson($response);
    }
    
    protected function parseResponseJson(ResponseInterface $response)
    {
        try {
            $data = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            // @ToDo: custom exception?
            throw $e;
        }
        
        return $data;
    }
    
}
