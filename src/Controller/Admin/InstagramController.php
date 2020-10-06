<?php

namespace SocialData\Connector\Instagram\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Exception\ConnectException;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InstagramController extends AdminController
{
    use ConnectResponseTrait;

    /**
     * @var InstagramClient
     */
    protected $instagramClient;

    /**
     * @var EnvironmentServiceInterface
     */
    protected $environmentService;

    /**
     * @var ConnectorServiceInterface
     */
    protected $connectorService;

    /**
     * @param InstagramClient             $instagramClient
     * @param EnvironmentServiceInterface $environmentService
     * @param ConnectorServiceInterface   $connectorService
     */
    public function __construct(
        InstagramClient $instagramClient,
        EnvironmentServiceInterface $environmentService,
        ConnectorServiceInterface $connectorService
    ) {
        $this->instagramClient = $instagramClient;
        $this->environmentService = $environmentService;
        $this->connectorService = $connectorService;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function connectAction(Request $request)
    {
        $error = null;
        $redirectUrl = null;

        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig();
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        try {
            $redirectUrl = $this->instagramClient->generateRedirectUrl($connectorEngineConfig);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error !== null) {
            return $this->buildConnectErrorResponse(500, 'general_error', sprintf('connect error with api type "%s"', $connectorEngineConfig->getApiType()), $error);
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function checkAction(Request $request)
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig();
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        try {
            $tokenData = $this->instagramClient->generateAccessTokenFromRequest($connectorEngineConfig, $request);
        } catch (ConnectException $e) {
            return $this->buildConnectErrorByExceptionResponse($e);
        }

        if (!is_array($tokenData)) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'empty token data', 'could not generate token data');
        }

        $connectorEngineConfig->setAccessToken($tokenData['token'], true);
        $connectorEngineConfig->setAccessTokenExpiresAt($tokenData['expiresAt'], true);
        $this->connectorService->updateConnectorEngineConfiguration('instagram', $connectorEngineConfig);

        return $this->buildConnectSuccessResponse();
    }

    /**
     * @return EngineConfiguration
     */
    protected function getConnectorEngineConfig()
    {
        $connectorDefinition = $this->connectorService->getConnectorDefinition('instagram', true);

        if (!$connectorDefinition->engineIsLoaded()) {
            throw new HttpException(400, 'Engine is not loaded.');
        }

        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();
        if (!$connectorEngineConfig instanceof EngineConfiguration) {
            throw new HttpException(400, 'Invalid instagram configuration. Please configure your connector "instagram" in backend first.');
        }

        return $connectorEngineConfig;
    }
}
