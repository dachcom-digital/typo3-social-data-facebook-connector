<?php
declare(strict_types=1);

namespace DachcomDigital\SocialDataFacebook\Connector;

use DachcomDigital\SocialData\Connector\ConnectorInterface;
use DachcomDigital\SocialDataFacebook\Client\FacebookClient;

class FacebookConnector implements ConnectorInterface
{
    protected array $configuration;
    
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }
    
    public function fetchItems(): array
    {
        $client = new FacebookClient($this->configuration);
    
        $fields = [
            'id',
            'message',
            'story',
            'full_picture',
            'permalink_url',
            'created_time',
            'attachments',
            'status_type',
            'is_published'
        ];
        
        $fetchedData = $client->get(
            sprintf('/%s/posts', $this->configuration['page_id']),
            [
                'fields' => implode(',', $fields),
                'limit' => 30
            ]
        );
        
        if (!array_key_exists('data', $fetchedData)) {
            return [];
        }
        $items = $fetchedData['data'];
        
        if (!is_array($items) || count($items) === 0) {
            return [];
        }
    
        $preparedItems = [];
        
        foreach ($items as $item) {
            $preparedItems[] = $this->getPreparedItem($item);
        }
    
        return $preparedItems;
    }
    
    protected function getPreparedItem($item): array
    {
        return [
            'id'         => $item['id'],
            'content'    => $item['message'],
            'datetime'   => \DateTime::createFromFormat(\DateTime::ISO8601, $item['created_time']),
            'url'        => $item['permalink_url'],
            'posterUrl'  => $item['full_picture']
        ];
    }
    
    protected function getClient(): FacebookClient
    {
        return new FacebookClient($this->configuration);
    }
}
