<?php

namespace Tests\Feature;

use Tests\AuthenticatedTestCase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RulesetTest extends AuthenticatedTestCase
{
    public function rulesetNameProvider() {
        return [
            ["mylists"],
            ["yourlists"]
        ];
    }

    /**
     * We want to upload ruleset under two different namespaces so that we
     * can test the two deletion methods.
     *
     * @dataProvider rulesetNameProvider
     */
    public function testRulesetUpload($namespace) {
        //fopen("test-rulesets/test-lists.zip",)

        $response = $this->client->post('/api/admin/filterlists/upload', [
            'multipart' => [
                [
                    'name' => 'overwrite',
                    'contents' => 0
                ],

                [
                    'name' => 'namespace',
                    'contents' => $namespace
                ],

                [
                    'name' => 'file',
                    'contents' => fopen('test-rulesets/test-lists.zip', 'r'),
                    'filename' => 'test-lists.zip'
                ]
            ]
        ]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testRulesetUpload
     */
    public function testGetAllRulesets() {
        $response = $this->client->get('/api/admin/filterlist/all');

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody());

        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), "Invalid JSON");

        $this->assertEquals(8, count($json), "Incorrect number of rulesets");
    }

    /**
     * @depends testGetAllRulesets
     */
    public function testDeleteAllInNamespace() {
        $response = $this->client->delete('/api/admin/filterlists/namespace/mylists');

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function rulesetTypeProvider() {
        return [
            ['Filters'],
            ['Triggers']
        ];
    }

    /**
     * @depends testGetAllRulesets
     * @dataProvider rulesetTypeProvider
     */
    public function testDeleteTypeInNamespace($type) {
        $response = $this->client->delete("/api/admin/filterlists/namespace/yourlists/$type");

        $this->assertEquals(204, $response->getStatusCode());
    }
}
