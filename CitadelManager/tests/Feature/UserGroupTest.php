<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\AuthenticatedTestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserGroupTest extends AuthenticatedTestCase
{

    static $filterLists = null;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        parent::$globalClient->post('/api/admin/filterlists/upload', [
            'multipart' => [
                [
                    'name' => 'overwrite',
                    'contents' => 0
                ],

                [
                    'name' => 'namespace',
                    'contents' => 'testlists'
                ],

                [
                    'name' => 'file',
                    'contents' => fopen('test-rulesets/test-lists.zip', 'r'),
                    'filename' => 'test-lists.zip'
                ]
            ]
        ]);

        $response = parent::$globalClient->get('/api/admin/filterlist/all');

        self::$filterLists = json_decode($response->getBody());


        // TODO: We really should create an app group + app too, to test this.
    }

    // TODO: testInvalidUserGroupCreate(). Should return 400 instead of 200.

    public function testUserGroupCreate() {
        $userGroup = [
            "name" => "Not-Appropriate-For-Use",
            "isactive" => 1,
            "assigned_filter_ids" => [
                [
                    "filter_list_id" => self::$filterLists[0]->id,
                    "as_blacklist" => 1,
                    "as_whitelist" => 0,
                    "as_bypass" => 0
                ]
            ],

            "app_cfg" => [
                "UpdateFrequency" => 5,
                "PrimaryDns" => "8.8.8.8",
                "SecondaryDns" => "8.8.4.4",
                "CannotTerminate" => false,
                "BlockInternet" => false,
                "UseThreshold" => false,
                "ThresholdLimit" => 0,
                "ThresholdTriggerPeriod" => 0,
                "ThresholdTimeoutPeriod" => 0,
                "BypassesPermitted" => 0,
                "BypassDuration" => 0,
                "NlpThreshold" => 0,
                "MaxTextTriggerScanningSize" => 0,
                "UpdateChannel" => "Stable",
                "ReportLevel" => 0,
                "Blacklist" => "checked"
            ]
        ];

        $response = $this->client->post('/api/admin/groups', $userGroup);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testUserGroupGetAll() {
        $response = $this->client->get('/api/admin/groups?draw=1');

        $this->assertEquals(200, $response->getStatusCode());

        $this->markTestIncomplete('This test does not have columns code.');
    }

    public function testUserGroupEdit() {
        // TODO
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testUserGroupDelete() {
        // TODO
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
