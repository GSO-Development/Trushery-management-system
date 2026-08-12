<?php

namespace App\Services\Auth;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class MicrosoftProvider extends AbstractProvider implements ProviderInterface
{
    protected string $tenant;

    protected $scopes = ['openid', 'profile', 'email', 'User.Read'];

    protected $scopeSeparator = ' ';

    protected function getTenant(): string
    {
        return config('services.microsoft.tenant', 'common');
    }

    protected function getAuthUrl($state): string
    {
        $tenant = $this->getTenant();

        return $this->buildAuthUrlFromBase(
            "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize",
            $state
        );
    }

    protected function getTokenUrl(): string
    {
        $tenant = $this->getTenant();

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            'https://graph.microsoft.com/v1.0/me',
            [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     * Automatically disables SSL verification in local environment to prevent cURL error 60 on Windows.
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $options = $this->guzzle;

            if (app()->isLocal() || config('services.microsoft.verify') === false) {
                $options['verify'] = false;
            }

            $this->httpClient = new \GuzzleHttp\Client($options);
        }

        return $this->httpClient;
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id']               ?? null,
            'name'     => $user['displayName']       ?? null,
            'email'    => $user['mail']              ?? $user['userPrincipalName'] ?? null,
            'nickname' => $user['userPrincipalName'] ?? null,
            'avatar'   => null,
        ]);
    }
}
