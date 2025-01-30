<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace SocialData\Connector\Instagram\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\InstagramIdentityProviderException;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Instagram;
use League\OAuth2\Client\Token\AccessToken;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Exception\ConnectException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InstagramClient
{
    protected const GRAPH_VERSION = 'v22.0';
    public const API_INSTAGRAM_LOGIN = 'instagram_login';
    public const API_FACEBOOK_LOGIN = 'facebook_login';

    public function __construct(
        protected RequestStack $requestStack,
        protected UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @throws ConnectException
     */
    public function generateConnectUrl(EngineConfiguration $connectorEngineConfiguration, ConnectorDefinitionInterface $connectorDefinition): ?string
    {
        $provider = $this->getProvider($connectorEngineConfiguration);
        $definitionConfiguration = $connectorDefinition->getDefinitionConfiguration();

        $scope = $definitionConfiguration['api_connect_permission_instagram_login'];
        if ($connectorEngineConfiguration->getApiType() === self::API_FACEBOOK_LOGIN) {
            $scope = $definitionConfiguration['api_connect_permission_facebook_login'];
        }

        try {
            $authUrl = $provider->getAuthorizationUrl(['scope' => $scope]);
        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', 'redirect url generation error');
        }

        $this->getSession()->set('IGRLH_oauth2state_social_data', $provider->getState());

        return $authUrl;
    }

    /**
     * @throws ConnectException
     */
    public function generateAccessTokenFromRequest(EngineConfiguration $connectorEngineConfiguration, Request $request): array
    {
        $provider = $this->getProvider($connectorEngineConfiguration);

        try {
            $token = $provider->getAccessToken('authorization_code', ['code' => $request->query->get('code')]);
        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', 'token access error');
        }

        if (!$token instanceof AccessToken) {
            $message = 'Could not generate access token';
            if ($request->query->has('error_message')) {
                $message = $request->query->get('error_message');
            }

            throw new ConnectException($message, 500, 'general_error', 'token access error');
        }

        if (!method_exists($provider, 'getLongLivedAccessToken')) {
            throw new ConnectException(
                'Provider require to implement the "getLongLivedAccessToken" method',
                500,
                'general_error',
                'token access error'
            );
        }

        try {
            $accessToken = $provider->getLongLivedAccessToken($token->getToken());
        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', 'exchange token error');
        }

        return [
            'token'     => $accessToken->getToken(),
            'expiresAt' => $accessToken->getExpires() !== null ? \DateTime::createFromFormat('U', $accessToken->getExpires()) : null
        ];
    }

    /**
     * @throws \Exception
     * @throws InstagramIdentityProviderException
     */
    public function refreshAccessToken(EngineConfiguration $engineConfiguration, ?string $token): AccessToken
    {
        $provider = $this->getProvider($engineConfiguration);

        if (!$provider instanceof Instagram) {
            throw new \Exception(sprintf('unsupported provider "%s"', get_class($provider)));
        }

        return $provider->getRefreshedAccessToken($token);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function makeCall(string $endpoint, string $method, EngineConfiguration $configuration, array $queryParams = [], array $formParams = []): array
    {
        $client = $this->getGuzzleClient($configuration);

        $params = [
            'query' => array_merge([
                'access_token'    => $configuration->getAccessToken(),
                'appsecret_proof' => hash_hmac('sha256', $configuration->getAccessToken(), $configuration->getAppSecret()),
            ], $queryParams)
        ];

        if (count($formParams) > 0) {
            $params['form_params'] = $formParams;
        }

        $response = $client->request($method, ltrim($endpoint, '/'), $params);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \Exception|GuzzleException
     */
    public function makeGraphCall(string $query, EngineConfiguration $configuration): array
    {
        $client = $this->getGuzzleClient($configuration);

        $endpoint = sprintf(
            '%s&access_token=%s',
            ltrim($query, '/'),
            $configuration->getAccessToken()
        );

        return json_decode($client->get($endpoint)->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \Exception
     */
    public function getProvider(EngineConfiguration $configuration): AbstractProvider
    {
        if ($configuration->getApiType() === self::API_FACEBOOK_LOGIN) {

            if (!class_exists(Facebook::class)) {
                throw new \InvalidArgumentException('Facebook Service not found. Make sure that league/oauth2-facebook is installed!');
            }

            return new Facebook([
                'clientId'        => $configuration->getAppId(),
                'clientSecret'    => $configuration->getAppSecret(),
                'redirectUri'     => $this->generateConnectUri(),
                'graphApiVersion' => self::GRAPH_VERSION,
            ]);
        }

        return new Instagram([
            'clientId'        => $configuration->getAppId(),
            'clientSecret'    => $configuration->getAppSecret(),
            'redirectUri'     => $this->generateConnectUri(),
            'graphApiVersion' => self::GRAPH_VERSION,
        ]);
    }

    protected function getGuzzleClient(EngineConfiguration $configuration): Client
    {
        $baseUri = sprintf('graph.instagram.com/%s/me/', self::GRAPH_VERSION);

        if ($configuration->getApiType() === self::API_FACEBOOK_LOGIN) {
            $baseUri = sprintf('https://graph.facebook.com/%s/', self::GRAPH_VERSION);
        }

        return new Client([
            'base_uri' => $baseUri
        ]);
    }

    protected function generateConnectUri(): string
    {
        return $this->urlGenerator->generate(
            'social_data_connector_instagram_connect_check',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    protected function getSession(): SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \LogicException('Cannot get the session without an active request.');
        }

        return $request->getSession();
    }
}
