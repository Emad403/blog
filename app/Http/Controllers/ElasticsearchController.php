<?php

namespace App\Http\Controllers;

use App\Services\ElasticsearchService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ElasticsearchController extends Controller
{
    protected $elasticsearch;

    public function __construct(ElasticsearchService $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Search for documents.
     */
    public function search()
    {
        $params = [
            'index' => 'your_index',
            'body' => [
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

    public function searchData(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255', // Search text
            'sort_by' => 'nullable|in:date,publisher', // Sort field
            'order' => 'nullable|in:asc,desc', // Sort order
        ]);

        //sort and order
        $sortBy = $validated['sort_by'] ?? 'news_timestamp'; // Default to date
        $order = $validated['order'] ?? 'desc'; // Default to descending

        //search query
        $query = [
            'index' => '*', // Search across all indices
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [], // Add query clauses here
                    ],
                ],
                'sort' => [
                    $sortBy => ['order' => $order],
                ],
            ],
        ];

        // Add text search if provided
        if (!empty($validated['query'])) {
            $query['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $validated['query'],
                    'fields' => ['news_title', 'news', 'post_description'], // Fields to search in
                ],
            ];
        }

        try {
            // Execute search query in Elasticsearch
            $results = $this->elasticsearch->search($query);

            // Format response
            $data = array_map(function ($hit) {
                return $hit['_source'];
            }, $results['hits']['hits']);



            return response()->json(['data' => $data, 'total' => $results['hits']['total']['value']]);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['error' => 'Search failed', 'details' => $e->getMessage()], 500);
        }
    }

    public function addUser(Request $request){

        $data = $request->validate([
            'username' => 'required|string',
            'email' => 'required|email',
        ]);
        $data['ID'] = Str::uuid();

        $response = $this->elasticsearch->index([
            'index' => 'users',
            'id' => $data['ID'],
            'body' => $data,
        ]);
        

        
        return response()->json(['message' => 'user added succesfully', 'id' => $data['ID']]);

    }

    public function addUserNotifier(Request $request){
        Log::info('here1');

        $data = $request->validate([
            'id' => 'required|string',
            'sources' => 'required|array',
            'sources.*' => 'required|string|distinct',
        ]);
        Log::info('here2');

        $params = [
            'index' => 'users',
            'id' => $data['id'],
            'body' => [
                'sources' => $data['sources'],
            ],
        ];
        Log::info('here3');
        try {
            $response = $this->elasticsearch->update($params);
            if (isset($response['error'])) {
                return response()->json(['error' => $response['error']], 500);
            }
            return response()->json(['message' => 'notification sources has been updated succesfully',], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'notification sources has been updated succesfully', 'error' => $e], 500);
        }
    }

    public function getUsers(){
        
        $query = [
            'index' => 'users',
        ];
        try {
            $results = $this->elasticsearch->search($query);

            $data = array_map(function ($hit) {
                return $hit['_source'];
            }, $results['hits']['hits']);

            return response()->json(['data' => $data, 'total' => $results['hits']['total']['value']]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Search failed', 'details' => $e->getMessage()], 500);
        }
    }
    /**
     * Index a document.
     */
    public function indexDocument()
    {
        $params = [
            'index' => 'your_index',
            'id' => '1',
            'body' => [
                'field_name' => 'value',
            ],
        ];

        $response = $this->elasticsearch->index($params);

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
        //log($response);
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


