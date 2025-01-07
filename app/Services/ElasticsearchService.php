<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService
{
    protected $client;
    

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

    public function update(array $params)
    {
        return $this->client->update($params);
    }
}
