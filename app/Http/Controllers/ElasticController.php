<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Services\ElasticService;

class ElasticController extends Controller
{
    protected $elasticsearch;

    public function __construct(ElasticService $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function search()
    {
        return "ssss";
        $params = [
            'index' => 'your_index',
            'body'  => [
                'query' => [
                    'match' => [
                        'field_name' => 'search_term',
                    ],
                ],
            ],
        ];

        $response = $this->elasticsearch->search($params);

        return response()->json($response);
    }

    public function addNews(Request $request)
    {
        $data = $request->validate([
            'ID' => 'required|string',
            'news_timestamp' => 'required|date',
            'news_source' => 'required|string',
            'news_title' => 'required|string',
            'news' => 'required|string',
            'news_link' => 'required|url',
        ]);

        $response = $this->elasticsearch->index([
            'index' => 'news_channel',
            'id' => $data['ID'],
            'body' => $data,
        ]);

        return response()->json(['message' => 'News added', 'result' => $response]);
    }

    public function addInstagram(Request $request)
    {
        $data = $request->validate([
            'ID' => 'required|string',
            'post_timestamp' => 'required|date',
            'post_type' => 'required|string',
            'post_description' => 'required|string',
            'sender_username' => 'required|string',
            'post_link' => 'required|url',
        ]);

        $response = $this->elasticsearch->index([
            'index' => 'instagram',
            'id' => $data['ID'],
            'body' => $data,
        ]);

        return response()->json(['message' => 'Instagram post added', 'result' => $response]);
    }

    public function addTwitter(Request $request)
    {
        $data = $request->validate([
            'ID' => 'required|string',
            'post_timestamp' => 'required|date',
            'post_description' => 'required|string',
            'sender_username' => 'required|string',
            'post_link' => 'required|url',
        ]);

        $response = $this->elasticsearch->index([
            'index' => 'twitter',
            'id' => $data['ID'],
            'body' => $data,
        ]);

        return response()->json(['message' => 'Twitter post added', 'result' => $response]);
    }
}