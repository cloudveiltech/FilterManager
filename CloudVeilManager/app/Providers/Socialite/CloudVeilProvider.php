<?php

namespace App\Providers\Socialite;

// use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\AbstractProvider;

//use Socialite\Providers\Manager\OAuth2\AbstractProvider;

class CloudVeilProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'CLOUDVEIL';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.cloudveil.org/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.cloudveil.org/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://www.cloudveil.org/oauth/me?access_token=' . $token
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        //dd($user);
        return (new User())->setRaw($user)->map(
            [
                'id' => $user['ID'],
                'name' => array_get($user, 'user_login'),
                'email' => $user['user_email']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
