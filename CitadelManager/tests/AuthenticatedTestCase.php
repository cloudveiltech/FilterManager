<?php
/**
 * Created by PhpStorm.
 * User: kent
 * Date: 7/24/18
 * Time: 1:42 PM
 */

namespace Tests;

abstract class AuthenticatedTestCase extends TestCase
{
    /**
     * @var \GuzzleHttp\Client
     */
    static $globalClient = null;

    /**
     * @var \GuzzleHttp\Client
     */
    public $client = null;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

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
}