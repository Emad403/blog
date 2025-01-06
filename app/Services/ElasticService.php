<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;

class ElasticService
{
    protected $client;
    private $config = [
      '127.0.0.1:9200',
    ];
    

    public function __construct()
    {
        $this->client = ClientBuilder::create()
        ->setHosts(config('database.connections.elastic.host'))
        ->build();
    }

    public function search(array $params)
    {
        return $this->client->search($params);
    }

    public function index(array $params)
    {
        return $this->client->index($params);
    }

    public function delete(array $params)
    {
        return $this->client->delete($params);
    }
}
