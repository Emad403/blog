<?php

namespace Tests\Feature;

use Tests\TestCase;
use Elastic\Elasticsearch\ClientBuilder;
use Mockery;


class ElasticsearchControllerTest extends TestCase
{

    protected $elasticsearch;


    protected function setUp(): void
    {
        parent::setUp();
        $this->elasticsearch = ClientBuilder::create()->build();
    }

    public function test_add_user_with_fake_data()
    {
        $faker = \Faker\Factory::create();
        $userData = [
            'username' => $faker->userName,
            'email' => $faker->safeEmail,
        ];

        $response = $this->json('POST', '/api/add-user', $userData);
        $response->assertStatus(200)->assertJsonStructure([
            'message',
            'id',
        ]);

        $userId = $response->json('id');
        $esResponse = $this->elasticsearch->get([
            'index' => 'users',
            'id' => $userId,
        ]);

        $this->assertEquals($userData['username'], $esResponse['_source']['username']);
        $this->assertEquals($userData['email'], $esResponse['_source']['email']);
    }


    public function test_add_user_validation_failure()
    {
        $userData = [
            'email' => 'e@example.com',
        ];
        $response = $this->json('POST', '/api/add-user', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    }

    public function test_add_user_email_validation_failure()
    {
        $userData = [
            'username' => 'e',
            'email' => 'email',
        ];

        $response = $this->json('POST', '/api/add-user', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // we can have all kind of needed test for each api call we can use mock for simulating 
    // elastic failure and ... 


}


