<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotifications;
use App\Services\ElasticsearchService;
use Faker\Factory;
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

    public function addUser(Request $request){
        $data = $request->validate([
            'username' => 'required|string',
            'email' => 'required|email',
        ]);
        $data['id'] = Str::uuid();
        $data['sources'] = [];    
         
        try {
            // we can also check for email and username in our elastic user index
            // before inserting new user to ensure that usernames and emails are unique
            $response = $this->elasticsearch->index([
                'index' => 'users',
                'id' => $data['id'],
                'body' => $data,
            ]);

            return response()->json(['message' => 'user added successfully', 'id' => $data['id']]);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }

    }

    public function addUserNotifier(Request $request){

        $data = $request->validate([
            'id' => 'required|string',
            'sources' => 'required|array|max:10',
            'sources.*' => 'string|distinct',
        ]);
        
        // we can get current sources for each user and handle the function in a way
        // that if users wants add new source system checks if the data already exist in
        // user sources and check maximum of 10 source for the user but as this is test 
        // project we override the sources and check the number of sources with laravel 
        // built in validator
        $params = [
            'index' => 'users',
            'id' => $data['id'],
            'body' => [
                'script' => [
                    'source' => 'ctx._source.sources = params.new_sources',
                    'params' => [
                        'new_sources' => $data['sources'],
                    ],
                ],
            ],
        ];
        try {
            $response = $this->elasticsearch->update($params);
            if (isset($response['error'])) {
                Log::info($response['error']);
                return response()->json(['error' => 'something went wrong'], 500);
            }
            return response()->json(['message' => 'notification sources has been updated successfully',], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    public function getUsers(){
        
        $query = [
            'index' => 'users',
            'size' => 1000,
        ];
        try {
            $results = $this->elasticsearch->search($query);

            $data = array_map(function ($hit) {
                return $hit['_source'];
            }, $results['hits']['hits']);

            return response()->json(['data' => $data, 'total' => $results['hits']['total']['value']]);
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    public function getUser(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|uuid|max:255|min:10',
        ]);

        $query = [
            'index' => 'users',
            'body' => [
                'query' => [
                    'match' => [
                        'id' => $data['id']
                    ]
                ],
            ],
        ];

        try {
            $results = $this->elasticsearch->search($query);

            $data = array_map(function ($hit) {
                return $hit['_source'];
            }, $results['hits']['hits']);

            return response()->json(['data' => $data, 'total' => $results['hits']['total']['value']]);

        } catch (\Exception $e) {
            Log::info($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    public function addNews(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'timestamp' => 'required|date',
            'source' => 'required|string',
            'title' => 'required|string',
            'news' => 'required|string',
            'link' => 'required|url',
        ]);

        try {
            $response = $this->elasticsearch->index([
                'index' => 'news',
                'id' => $data['id'],
                'body' => $data,
            ]);

            $query = [
                'index' => 'users',
                'size' => 1000,
                'body'  => [
                    'query' => [
                        'term' => [
                            'sources.keyword' => $data['source'], 
                        ],
                    ],
                ],
            ];
            $users = $this->elasticsearch->search($query);
            $res = array_map(function ($hit) {
                return $hit['_source'];
            }, $users['hits']['hits']);

            // we can call sendNotifications service here
            // we can implement message broker or queues for this in production in order to prevent failure and ...
            // also we can send email to our users after smtp setup or any other kind of notification model
            // if we used relational database for our user we could select and find the users in order to send the notification
            // also we can use the laravel queue for this purpose at setup the worker as we see fit
            //SendNotifications::dispatch($data, $res);

            return response()->json(['message' => 'News added', 'notification sent to these users:' => $res]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
        
    }

    public function addInstagram(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'timestamp' => 'required|date',
            'type' => 'required|string',
            'description' => 'required|string',
            'source' => 'required|string',
            'link' => 'required|url',
        ]);
        try {
            $response = $this->elasticsearch->index([
                'index' => 'instagram',
                'id' => $data['id'],
                'body' => $data,
            ]);
        
            $query = [
                'index' => 'users',
                'size' => 1000,
                'body'  => [
                    'query' => [
                        'term' => [
                            'sources.keyword' => $data['source'], 
                        ],
                    ],
                ],
            ];
            $users = $this->elasticsearch->search($query);
            $res = array_map(function ($hit) {
                return $hit['_source'];
            }, $users['hits']['hits']);

            // we can call sendNotifications service here
            // we can implement message broker or queues for this in production in order to prevent failure and ...
            // also we can send email to our users after smtp setup or any other kind of notification model
            // if we used relational database for our user we could select and find the users in order to send the notification
            // also we can use the laravel queue for this purpose at setup the worker as we see fit
            //SendNotifications::dispatch($data, $res);

            return response()->json(['message' => 'Instagram post added', 'notification sent to these users:' => $res]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    public function addTwitter(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'timestamp' => 'required|date',
            'description' => 'required|string',
            'source' => 'required|string',
            'link' => 'required|url',
        ]);

        try {
            $response = $this->elasticsearch->index([
                'index' => 'twitter',
                'id' => $data['id'],
                'body' => $data,
            ]);
            $query = [
                'index' => 'users',
                'size' => 1000,
                'body'  => [
                    'query' => [
                        'term' => [
                            'sources.keyword' => $data['source'], 
                        ],
                    ],
                ],
            ];
            $users = $this->elasticsearch->search($query);
            $res = array_map(function ($hit) {
                return $hit['_source'];
            }, $users['hits']['hits']);

            // we can call sendNotifications service here
            // we can implement message broker or queues for this in production in order to prevent failure and ...
            // also we can send email to our users after smtp setup or any other kind of notification model
            // if we used relational database for our user we could select and find the users in order to send the notification
            // also we can use the laravel queue for this purpose at setup the worker as we see fit
            //SendNotifications::dispatch($data, $res);

            return response()->json(['message' => 'Twitter post added', 'notification sent to these users:' => $res]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    // if wanted to store all 3 model of data in single index as most of the field are common
    // we could normalize the data and use platform type as parameter  to have all the data in 
    // single index we could also have 3 different api normalize data in each api and store the
    //data on single index

    public function addPost(Request $request)
    {
        $req = $request->validate([
            'id' => 'required|string',
            'timestamp' => 'required|date',
            'platform' => 'required|string',
            'news' => 'string',
            'description' => 'string',
            'source' => 'required|string',
            'title' => 'string',
            'type' => 'string',
            'link' => 'required|url',
        ]);
        $data = [
            'id' => $req['id'],
            'timestamp' => $req['timestamp'],
            'platform' => $req['platform'],
            'link' => $req['link'],
            'source' => $req['source'],
        ];

        if ($data['platform'] == 'instagram') {
            if (empty($req['description']) || empty($req['type'])) {
                return response()->json(['message' => 'data not valid'], 422);
            } else {
                $data['text'] = $req['description'];
                $data['type'] = $req['type'];
                $data['title'] = '';
            }
        } else if ($data['platform'] == 'news') {
            if (empty($req['title']) || empty($req['news'])) {
                return response()->json(['message' => 'data not valid'], 422);
            } else {
                $data['text'] = $req['news'];
                $data['type'] = '';
                $data['title'] = $req['title'];
            }
        } else if ($data['platform'] == 'twitter') {
            if (empty($req['description'])) {
                return response()->json(['message' => 'data not valid'], 422);
            } else {
                $data['text'] = $req['description'];
                $data['type'] = '';
                $data['title'] = '';
            }
        } else {
            return response()->json(['message' => 'data not valid'], 422);
        }
        

        try {
            $response = $this->elasticsearch->index([
                'index' => 'posts',
                'id' => Str::uuid(),
                'body' => $data,
            ]);
            $query = [
                'index' => 'users',
                'body'  => [
                    'query' => [
                        'term' => [
                            'sources.keyword' => $data['source'], 
                        ],
                    ],
                ],
            ];
            $users = $this->elasticsearch->search($query);
            $res = array_map(function ($hit) {
                return $hit['_source'];
            }, $users['hits']['hits']);

            // we can call sendNotifications service here
            // we can implement message broker or queues for this in production in order to prevent failure and ...
            // also we can send email to our users after smtp setup or any other kind of notification model
            // if we used relational database for our user we could select and find the users in order to send the notification
            // also we can use the laravel queue for this purpose at setup the worker as we see fit
            //SendNotifications::dispatch($data, $res);

            return response()->json(['message' => 'Post added', 'notification sent to these users:' => $res]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    // we could also implement pagination if needed
    // this is finding on all index if needed we could have specified the platform as and input
    // to use the specified platform index for our search or have multiple api calls for different
    // indexed and use cases
    public function searchData(Request $request)
    {
        $validated = $request->validate([
            'source' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'news' => 'nullable|string',
            'text' => 'nullable|string',
        ]);


        $params = [
            'index' => ['news', 'instagram', 'twitter', 'posts'],
            'size' => 1000, 
            'body'  => [
                'query' => [
                    'bool' => [
                        'should' => []
                    ]
                ]
            ]
        ];

        if ($request->has('source') && $request->input('source')) {
            $params['body']['query']['bool']['should'][] = [
                'match' => [
                    'source' => $request->input('source')
                ]
            ];
        }
        if ($request->has('description') && $request->input('description')) {
            $params['body']['query']['bool']['should'][] = [
                'match' => [
                    'description' => $request->input('description')
                ]
            ];
        }
        if ($request->has('news') && $request->input('news')) {
            $params['body']['query']['bool']['should'][] = [
                'match' => [
                    'news' => $request->input('news')
                ]
            ];
        }
        if ($request->has('text') && $request->input('text')) {
            $params['body']['query']['bool']['should'][] = [
                'match' => [
                    'text' => $request->input('text')
                ]
            ];
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $params['body']['query']['bool']['should'][] = [
                'range' => [
                    'timestamp' => [
                        'gte' => $request->input('start_date'),
                        'lte' => $request->input('end_date')
                    ]
                ]
            ];
        } else if ($request->has('start_date')) {
            $params['body']['query']['bool']['should'][] = [
                'range' => [
                    'timestamp' => [
                        'gte' => $request->input('start_date'),
                    ]
                ]
            ];
        } else {
            $params['body']['query']['bool']['should'][] = [
                'range' => [
                    'timestamp' => [
                        'lte' => $request->input('end_date'),
                    ]
                ]
            ];
        }

        try {
            $results = $this->elasticsearch->search($params);

            $data = array_map(function ($hit) {
                return $hit['_source'];
            }, $results['hits']['hits']);
            return response()->json(['data' => $results['hits']['hits'], 'total' => $results['hits']['total']['value']]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Search failed', 'details' => $e->getMessage()], 500);
        }
    }

    public function addFakeData()
    {
        $faker = Factory::create();
        $fakeUsers = [];
        $fakeNews = [];
        $fakeReels = [];
        $fakeTweets = [];
        $fakePosts = [];

        //users
        for ($i = 0; $i < 1; $i++) {
            $userData = [
                'id' => Str::uuid(),
                'username' => $faker->userName,
                'email' => $faker->unique()->safeEmail,
                'sources' => [(string)$faker->numberBetween(0,100)],
            ];

            $fakeUsers[] = $userData;

            $this->elasticsearch->index([
                'index' => 'users',
                'id' => $userData['id'],
                'body' => $userData,
            ]);
        }

        //news
        for ($i = 0; $i < 100; $i++) {
            $News = [
                'id' => Str::uuid(),
                'timestamp' => $faker->date('Y-m-d\TH:i:s','now'),
                'source' => $faker->userName(),
                'title' => $faker->sentence,
                'news' => $faker->sentence,
                'link' => $faker->url,
            ];

            $fakeNews[] = $News;

            $this->elasticsearch->index([
                'index' => 'news',
                'id' => $News['id'],
                'body' => $News,
            ]);
        }

        //instagram
        for ($i = 0; $i < 100; $i++) {
            $post = [
                'id' => Str::uuid(),
                'timestamp' => $faker->date('Y-m-d\TH:i:s','now'),
                'source' => $faker->userName(),
                'description' => $faker->sentence,
                'type' => $faker->numerify('#'),
                'link' => $faker->url,
            ];

            $fakePosts[] = $post;

            $this->elasticsearch->index([
                'index' => 'instagram',
                'id' => $post['id'],
                'body' => $post,
            ]);
        }

        //twitter
        for ($i = 0; $i < 100; $i++) {
            $tweet = [
                'id' => Str::uuid(),
                'timestamp' => $faker->date('Y-m-d\TH:i:s','now'),
                'source' => $faker->userName(),
                'description' => $faker->sentence,
                'link' => $faker->url,
            ];

            $fakeTweets[] = $tweet;

            $this->elasticsearch->index([
                'index' => 'twitter',
                'id' => $tweet['id'],
                'body' => $tweet,
            ]);
        }

        //posts
        for ($i = 0; $i < 100; $i++) {
            $post = [
                'id' => Str::uuid(),
                'timestamp' => $faker->date('Y-m-d\TH:i:s','now'),
                'source' => $faker->userName(),
                'type' => $faker->numerify('#'),
                'platform' => $faker->randomElement(['instagram','twitter', 'news']),
                'title' => $faker->sentence,
                'text' => $faker->sentence,
                'link' => $faker->url,
            ];

            $fakePosts[] = $post;

            $this->elasticsearch->index([
                'index' => 'posts',
                'id' => $post['id'],
                'body' => $post,
            ]);
        }

        return response()->json([
            'message' => 'fake data added successfully',
            'users' => $fakeUsers,
            'news' => $fakeNews,
            'instagram' => $fakeReels,
            'twitter' => $fakeTweets,
            'posts' => $fakePosts,
        ]);
    }
}


