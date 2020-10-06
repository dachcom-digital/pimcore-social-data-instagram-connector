<?php

namespace SocialData\Connector\Instagram\Client;

use Carbon\Carbon;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookSDKException;
use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplay;
use EspressoDev\InstagramBasicDisplay\InstagramBasicDisplayException;
use SocialData\Connector\Instagram\Session\InstagramDataHandler;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Exception\ConnectException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InstagramClient
{
    const API_PRIVATE = 'private';
    const API_BUSINESS = 'business';

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param SessionInterface      $session
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(SessionInterface $session, UrlGeneratorInterface $urlGenerator)
    {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param EngineConfiguration $connectorEngineConfiguration
     *
     * @return string|null
     *
     * @throws ConnectException
     */
    public function generateRedirectUrl(EngineConfiguration $connectorEngineConfiguration)
    {
        $redirectUrl = null;
        $client = $this->getClient($connectorEngineConfiguration);

        try {
            if ($client instanceof InstagramBasicDisplay) {
                $redirectUrl = $client->getLoginUrl();
            } elseif ($client instanceof Facebook) {
                $helper = $client->getRedirectLoginHelper();
                // @todo: make this configurable (e.g. via connector config?)
                $redirectUrl = $helper->getLoginUrl($this->generateConnectUri(), ['pages_show_list', 'instagram_basic']);
            }
        } catch (\Throwable $e) {
            throw new ConnectException($e->getMessage(), 500, 'general_error', 'redirect url generation error');
        }

        return $redirectUrl;
    }

    /**
     * @param EngineConfiguration $connectorEngineConfiguration
     * @param Request             $request
     *
     * @return array|null
     *
     * @throws ConnectException
     */
    public function generateAccessTokenFromRequest(EngineConfiguration $connectorEngineConfiguration, Request $request)
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

            return [
                'token'     => $accessToken->getValue(),
                'expiresAt' => $accessToken->getExpiresAt()
            ];
        }

        return null;
    }

    public function refreshAccessToken(EngineConfiguration $connectorEngineConfiguration)
    {

    }

    /**
     * @param EngineConfiguration $connectorEngineConfiguration
     *
     * @return InstagramBasicDisplay|Facebook
     *
     * @throws ConnectException
     */
    public function getClient(EngineConfiguration $connectorEngineConfiguration)
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
     * @param EngineConfiguration $configuration
     *
     * @return InstagramBasicDisplay
     *
     * @throws InstagramBasicDisplayException
     * @throws \Exception
     */
    protected function getPrivateClient(EngineConfiguration $configuration)
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
     * @param EngineConfiguration $configuration
     *
     * @return Facebook
     *
     * @throws FacebookSDKException
     * @throws \Exception
     */
    protected function getBusinessClient(EngineConfiguration $configuration)
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
     * @param \stdClass|null $response
     * @param array          $expectedValues
     *
     * @return array
     * @throws ConnectException
     */
    protected function parseBasicResponse(?\stdClass $response, array $expectedValues)
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

    /**
     * @return string
     */
    protected function generateConnectUri()
    {
        return $this->urlGenerator->generate('social_data_connector_instagram_connect_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}

