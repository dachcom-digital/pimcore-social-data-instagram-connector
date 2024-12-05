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
    protected const GRAPH_VERSION = 'v12.0';
    public const API_PRIVATE = 'private';
    public const API_BUSINESS = 'business';

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

        $scope = $definitionConfiguration['api_connect_permission_private'];
        if ($connectorEngineConfiguration->getApiType() === self::API_BUSINESS) {
            $scope = $definitionConfiguration['api_connect_permission_business'];
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

        try {
            $accessToken = $this->exchangeLongLivedAccessToken($connectorEngineConfiguration, $provider, $token->getToken());
        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', 'exchange token error');
        }

        return [
            'token'     => $accessToken->getToken(),
            'expiresAt' => $accessToken->getExpires() !== null ? \DateTime::createFromFormat('U', $accessToken->getExpires()) : null
        ];
    }

    protected function exchangeLongLivedAccessToken(EngineConfiguration $engineConfiguration, Instagram $provider, string $token): AccessToken
    {
        $params = ['client_secret' => $engineConfiguration->getAppSecret()];

        return $this->updateAccessToken($provider, $token, $params, 'ig_exchange_token');
    }

    public function refreshAccessToken(EngineConfiguration $engineConfiguration, ?string $token): AccessToken
    {
        $provider = $this->getProvider($engineConfiguration);

        return $this->updateAccessToken($provider, $token, [], 'ig_refresh_token');
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
    public function getProvider(EngineConfiguration $configuration): Instagram
    {
        return new Instagram([
            'clientId'        => $configuration->getAppId(),
            'clientSecret'    => $configuration->getAppSecret(),
            'redirectUri'     => $this->generateConnectUri(),
            'graphApiVersion' => self::GRAPH_VERSION,
        ]);
    }

    /**
     * @internal
     *
     * @deprecated replace with core if https://github.com/thephpleague/oauth2-instagram/pull/18 gets merged
     */
    protected function updateAccessToken(Instagram $provider, string $token, array $params, string $grant): AccessToken
    {
        $params = array_merge([
            'access_token' => $token,
            'grant_type'   => $grant,
        ], $params);

        $updateEndpoint = '';
        if ($grant === 'ig_exchange_token') {
            $updateEndpoint = 'access_token';
        } elseif ($grant === 'ig_refresh_token') {
            $updateEndpoint = 'refresh_access_token';
        }

        $query = http_build_query($params, '', '&', \PHP_QUERY_RFC3986);
        $url = sprintf('%s/%s?%s', $provider->getGraphHost(), $updateEndpoint, $query);

        $request = $provider->getRequest(Instagram::METHOD_GET, $url);
        $response = $provider->getParsedResponse($request);

        if (is_array($response) === false) {
            throw new \UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return new AccessToken($response);
    }

    protected function getGuzzleClient(EngineConfiguration $configuration): Client
    {
        $baseUri = 'https://graph.instagram.com/me/';

        if ($configuration->getApiType() === self::API_BUSINESS) {
            $baseUri = sprintf('https://graph.facebook.com/%s/', self::GRAPH_VERSION);
        }

        return new Client([
            'base_uri' => $baseUri
        ]);
    }

    protected function generateConnectUri(): string
    {
        return $this->urlGenerator->generate('social_data_connector_instagram_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
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
