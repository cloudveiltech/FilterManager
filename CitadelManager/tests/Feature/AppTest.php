<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AppTest extends TestCase
{
    /**
     * @var \GuzzleHttp\Client
     */
    static $globalClient = null;

    /**
     * @var \GuzzleHttp\Client
     */
    public $client = null;

    /**
     * @beforeClass
     */
    public static function setUpGlobal() {
        self::$globalClient = new \GuzzleHttp\Client([
            'base_uri' => 'https://localhost',

            'verify' => false,
            'cookies' => true
        ]);

        self::$globalClient->post('/login', [
            'form_params' => [
                'email' => 'kent@cloudveil.org',
                'password' => 'SamplePassword'
            ]
        ]);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->client = self::$globalClient;
    }

    public function appGroupWithoutAppsProvider()
    {
        return [
            ["Test Group"]
        ];
    }

    /**
     * Adding app groups test
     * 
     * @dataProvider appGroupWithoutAppsProvider
     */
    public function testAppGroupAddWithNoApps($groupName)
    {
        $user = factory(User::class)->create();

        $response = $this->client->post('/api/admin/app_group', [
            'form_params' => [
                'group_name' => $groupName,
                'apps' => []
            ]
        ]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function appDataWithoutGroupProvider()
    {
        return [
            ["test-app.exe", "Test App", 204],
            ["CloudVeil.exe", "CloudVeil GUI", 204],
            ["zoho.exe", "ZOHO", 204],
            ["example.exe", null, 204]
        ];
    }

    /**
     * Adding apps test
     * 
     * @dataProvider appDataWithoutGroupProvider
     * @return void
     */
    public function testAppAddWithoutGroup($name, $notes, $expected)
    {
    	// TODO: Use a data provider for this one.
        $response = $this->client->post('/api/admin/app', [
            "form_params" => [
                "name" => $name,
                "notes" => $notes
            ]
        ]);

        $this->assertEquals($expected, $response->getStatusCode());
    }

    /**
     *
     * @depends testAppGroupAddWithNoApps
     */
    public function testAppAddWithGroup()
    {
        $response = $this->client->post('/api/admin/app', [
            "form_params" => [
                "name" => "thunderpenguin.exe",
                "notes" => "ThunderPenguin",
                "assigned_appgroup" => [1]
            ]
        ]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     *
     * @depends testAppAddWithoutGroup
     */
    public function testAppGroupAddWithApp()
    {
        $response = $this->client->post('/api/admin/app_group', [
            "form_params" => [
                "group_name" => "My Awesome Test Group",
                "apps" => "1, 2"
            ]
        ]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testAppAddWithGroup
     */
    public function testAppGet()
    {
        $response = $this->client->get('/api/admin/get_appgroup_data/1');

        $this->assertEquals(200, $response->getStatusCode());

        $json = $response->getBody();

        $jsonObj = json_decode($json);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Invalid JSON returned from testAppGet()");

        return $jsonObj;
    }

    /**
     * @depends testAppGet
     */
    public function testAppEdit($app)
    {
        $this->assertGreaterThan(0, count($app->app_groups), "No app groups have been added.");

        $editedApp = [
            "id" => 1,
            "name" => "test-app-modified.exe",
            "notes" => "Test App Modified",
            "assigned_appgroup" => []
        ];

        foreach($app->selected_app_groups as $selectedGroup) {
            $editedApp["assigned_appgroup"][] = $selectedGroup->app_group_id;
        }

        foreach($app->app_groups as $group) {
            $foundGroup = false;

            foreach($app->selected_app_groups as $selectedGroup) {
                if($group->id == $selectedGroup->app_group_id) {
                    $foundGroup = true;
                    break;
                }
            }

            if(!$foundGroup) {
                $editedApp["assigned_appgroup"][] = $group->id;
            }
        }

        $response = $this->client->patch("/api/admin/app/1", [
            "form_params" => $editedApp
        ]);

    	$this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testAppAddWithGroup
     */
    public function testGetApplicationsList()
    {
        // Why does this have to be so stinking difficult to build tests for this stuff?
        $response = $this->client->get("/api/admin/applications");

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();

        $json = json_decode($body);

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Invalid JSON");

        $this->assertGreaterThan(0, count($json), "No applications specified in JSON.");
    }

    /**
     * @depends testAppAddWithGroup
     */
    public function testAppGroupEdit()
    {
    	$response = $this->client->patch('/api/admin/app_group/1', [
    	    'form_params' => [
    	        'id' => 1,
    	        'group_name' => 'Modified Test Group',
                'apps' => implode(", ", [2,3,4])
            ]
        ]);

    	$this->assertEquals(204, $response->getStatusCode(), "204 No Content expected.");

    }

    // Grid getters aren't super important, because we still need to do GUI testing, and they'll be fairly obvious when they're
    // broken.
    public function testAppsGet()
    {
        // TODO: Write a little helper function to help with these nasty columns.
        // Reorder these as 
        $response = $this->client
            ->get('/api/admin/app', [
                "query" => [
                    "columns" => [
                        [
                            "data" => "id",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "name",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "notes",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "group_name",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "updated_at",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ]
                    ],

                    "draw" => 1
                ]
            ]);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody());

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Invalid JSON");

        $this->assertObjectHasAttribute("data", $json, "JSON object is malformed.");

        $this->assertGreaterThan(0, $json->data, "Data does not contain any apps.");
    }

    public function testAppGroupsGet()
    {
        $response = $this->client
            ->get('/api/admin/app', [
                "query" => [
                    "columns" => [
                        [
                            "data" => "id",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "group_name",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "app_names",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ],

                        [
                            "data" => "updated_at",
                            "name" => null,
                            "searchable" => true,
                            "orderable" => true,
                            "search" => [
                                "value" => null,
                                "regex" => false
                            ]
                        ]
                    ],

                    "draw" => 1
                ]
            ]);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody());

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Invalid JSON");

        $this->assertObjectHasAttribute("data", $json, "JSON object is malformed.");

        $this->assertGreaterThan(0, $json->data, "Data does not contain any app groups.");
    }

    public function testAppDelete()
    {
        $response = $this->client->delete('/api/admin/app/1');

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testAppGroupDelete()
    {
        $response = $this->client->delete('/api/admin/app_group/1');

        $this->assertEquals(204, $response->getStatusCode());
    }
}
