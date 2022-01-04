<?php

namespace SocialData\Connector\Instagram\Client;

use Carbon\Carbon;
use SocialData\Connector\Instagram\Session\InstagramDataHandler;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Exception\ConnectException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InstagramClient
{
    public const API_PRIVATE = 'private';
    public const API_BUSINESS = 'business';

    protected SessionInterface $session;
    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(SessionInterface $session, UrlGeneratorInterface $urlGenerator)
    {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @throws ConnectException
     */
    public function generateRedirectUrl(EngineConfiguration $connectorEngineConfiguration, ConnectorDefinitionInterface $connectorDefinition): ?string
    {
        $redirectUrl = null;
        $client = $this->getClient($connectorEngineConfiguration);
        $definitionConfiguration = $connectorDefinition->getDefinitionConfiguration();

        try {
            if ($client instanceof InstagramBasicDisplay) {
                $redirectUrl = $client->getLoginUrl();
            } elseif ($client instanceof Facebook) {
                $helper = $client->getRedirectLoginHelper();
                $redirectUrl = $helper->getLoginUrl($this->generateConnectUri(), $definitionConfiguration['api_connect_permission_business']);
            }
        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', 'redirect url generation error');
        }

        return $redirectUrl;
    }

    /**
     * @throws ConnectException
     */
    public function generateAccessTokenFromRequest(EngineConfiguration $connectorEngineConfiguration, Request $request): ?array
    {
        $client = $this->getClient($connectorEngineConfiguration);

        if ($client instanceof InstagramBasicDisplay) {

            try {
                $accessToken = $client->getOAuthToken($request->query->get('code'));
            } catch (\Throwable $e) {
                throw new ConnectException($e->getMessage(), 500, 'general_error', 'oauth token access error');
            }

            $responseData = $this->parseBasicResponse($accessToken, ['access_token']);

            try {
                $longLivedToken = $client->getLongLivedToken($responseData['access_token']);
            } catch (\Throwable $e) {
                throw new ConnectException($e->getMessage(), 500, 'general_error', 'oauth long live token exchange access token error');
            }

            $responseData = $this->parseBasicResponse($longLivedToken, ['access_token', 'expires_in']);

            $expireDate = Carbon::now();
            $expireDate->addSeconds($responseData['expires_in']);

            return [
                'token'     => $responseData['access_token'],
                'expiresAt' => $expireDate->toDateTime()
            ];
        }

        if ($client instanceof Facebook) {

            try {
                $helper = $client->getRedirectLoginHelper();
                $accessToken = $helper->getAccessToken();
            } catch (\Throwable $e) {
                throw new ConnectException($e->getMessage(), 500, 'general_error', 'token access error');
            }

            if (empty($accessToken)) {
                throw new ConnectException(
                    $helper->getError() ? $helper->getErrorDescription() : $request->query->get('error_message', 'Unknown Error'),
                    $helper->getError() ? $helper->getErrorCode() : 500,
                    $helper->getError() ? $helper->getError() : 'general_error',
                    $helper->getError() ? $helper->getErrorReason() : 'invalid access token'
                );
            }

            try {
                $oAuth2Client = $client->getOAuth2Client();
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (FacebookSDKException $e) {
                throw new ConnectException($e->getMessage(), 500, 'general_error', 'long lived access token error');
            }

            try {
                // @todo: really? Dispatch the /me/accounts request to make the user token finally ever lasting.
                $response = ($client->get('/me/accounts?fields=access_token', $accessToken->getValue()))->getDecodedBody();
            } catch (FacebookSDKException $e) {
                // we don't need to fail here.
                // in worst case this means only we don't have a never expiring token
            }

            $accessTokenMetadata = $client->getOAuth2Client()->debugToken($accessToken->getValue());

            $expiresAt = null;
            if ($accessTokenMetadata->getExpiresAt() instanceof \DateTime) {
                $expiresAt = $accessTokenMetadata->getExpiresAt();
            }

            return [
                'token'     => $accessToken->getValue(),
                'expiresAt' => $expiresAt
            ];
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function refreshAccessToken(EngineConfiguration $connectorEngineConfiguration): array
    {
        $client = $this->getClient($connectorEngineConfiguration);

        if (!$client instanceof InstagramBasicDisplay) {
            throw new \Exception('Refresh token only works with the instagram basic display API');
        }

        try {
            $refreshedToken = $client->refreshToken($connectorEngineConfiguration->getAccessToken());
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        $responseData = $this->parseBasicResponse($refreshedToken, ['access_token', 'expires_in']);

        $expireDate = Carbon::now();
        $expireDate->addSeconds($responseData['expires_in']);

        return [
            'token'     => $responseData['access_token'],
            'expiresAt' => $expireDate->toDateTime()
        ];
    }

    /**
     * @param EngineConfiguration $connectorEngineConfiguration
     *
     * @return InstagramBasicDisplay|Facebook
     *
     * @throws ConnectException
     */
    public function getClient(EngineConfiguration $connectorEngineConfiguration): array
    {
        try {

            if ($connectorEngineConfiguration->getApiType() === InstagramClient::API_BUSINESS) {
                return $this->getBusinessClient($connectorEngineConfiguration);
            }

            if ($connectorEngineConfiguration->getApiType() === InstagramClient::API_PRIVATE) {
                return $this->getPrivateClient($connectorEngineConfiguration);
            }

        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', sprintf('client %s setup error', $connectorEngineConfiguration->getApiType()));
        }

        throw new ConnectException(sprintf('Invalid api type "%s"', $connectorEngineConfiguration->getApiType()), 500, 'general_error', 'client setup error');
    }

    /**
     * @throws InstagramBasicDisplayException
     * @throws \Exception
     */
    protected function getPrivateClient(EngineConfiguration $configuration): InstagramBasicDisplay
    {
        if ($configuration->getApiType() !== self::API_PRIVATE) {
            throw new \Exception('Engine does not allow usage of private client');
        }

        return new InstagramBasicDisplay([
            'appId'       => $configuration->getAppId(),
            'appSecret'   => $configuration->getAppSecret(),
            'redirectUri' => $this->generateConnectUri()
        ]);
    }

    /**
     * @throws FacebookSDKException
     * @throws \Exception
     */
    protected function getBusinessClient(EngineConfiguration $configuration): Facebook
    {
        if ($configuration->getApiType() !== self::API_BUSINESS) {
            throw new \Exception('Engine does not allow usage of business client');
        }

        return new Facebook([
            'app_id'                  => $configuration->getAppId(),
            'app_secret'              => $configuration->getAppSecret(),
            'persistent_data_handler' => new InstagramDataHandler($this->session),
            'default_graph_version'   => 'v8.0'
        ]);
    }

    /**
     * @throws ConnectException
     */
    protected function parseBasicResponse(?\stdClass $response, array $expectedValues): array
    {
        if (!$response instanceof \stdClass) {
            throw new ConnectException('basic response is empty', 500, 'parse_error', 'basic response error');
        }

        if (property_exists($response, 'error_message')) {
            $code = property_exists($response, 'code') ? $response->code : 500;
            $errorType = property_exists($response, 'error_type') ? $response->error_type : 'oauth_error';
            throw new ConnectException($response->error_message, $code, 'api_response_error', $errorType);
        }

        foreach ($expectedValues as $expectedValue) {
            if (!property_exists($response, $expectedValue)) {
                throw new ConnectException(sprintf('expected value "%s" missing in response', $expectedValue), 500, 'api_response_error', 'wrong response values');
            }
        }

        return get_object_vars($response);
    }

    protected function generateConnectUri(): string
    {
        return $this->urlGenerator->generate('social_data_connector_instagram_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}

