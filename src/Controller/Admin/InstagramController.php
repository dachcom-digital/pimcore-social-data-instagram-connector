<?php

namespace SocialData\Connector\Instagram\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Exception\ConnectException;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InstagramController extends AdminAbstractController
{
    use ConnectResponseTrait;

    public function __construct(
        protected InstagramClient $instagramClient,
        protected EnvironmentServiceInterface $environmentService,
        protected ConnectorServiceInterface $connectorService
    ) {
    }

    public function connectAction(Request $request): Response
    {
        $error = null;
        $redirectUrl = null;

        try {
            $connectorDefinition = $this->getConnectorDefinition();
            $connectorEngineConfig = $this->getConnectorEngineConfig($connectorDefinition);
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        try {
            $redirectUrl = $this->instagramClient->generateConnectUrl($connectorEngineConfig, $connectorDefinition);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error !== null) {
            return $this->buildConnectErrorResponse(500, 'general_error', sprintf('connect error with api type "%s"', $connectorEngineConfig->getApiType()), $error);
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * @throws \Exception
     */
    public function checkAction(Request $request): Response
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->buildConnectErrorResponse(500, 'general_error', 'connector engine configuration error', $e->getMessage());
        }

        if (!$request->query->has('state') || $request->query->get('state') !== $request->getSession()->get('IGRLH_oauth2state_social_data')) {
            return $this->buildConnectErrorResponse(400, 'general_error', 'missing state', 'Required param state missing from persistent data.');
        }

        try {
            $tokenData = $this->instagramClient->generateAccessTokenFromRequest($connectorEngineConfig, $request);
        } catch (ConnectException $e) {
            return $this->buildConnectErrorByExceptionResponse($e);
        }

        $connectorEngineConfig->setAccessToken($tokenData['token'], true);
        $connectorEngineConfig->setAccessTokenExpiresAt($tokenData['expiresAt'], true);
        $this->connectorService->updateConnectorEngineConfiguration('instagram', $connectorEngineConfig);

        return $this->buildConnectSuccessResponse();
    }

    protected function getConnectorDefinition(): ConnectorDefinitionInterface
    {
        $connectorDefinition = $this->connectorService->getConnectorDefinition('instagram', true);

        if (!$connectorDefinition->engineIsLoaded()) {
            throw new HttpException(400, 'Engine is not loaded.');
        }

        return $connectorDefinition;
    }

    protected function getConnectorEngineConfig(ConnectorDefinitionInterface $connectorDefinition): EngineConfiguration
    {
        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();
        if (!$connectorEngineConfig instanceof EngineConfiguration) {
            throw new HttpException(400, 'Invalid instagram configuration. Please configure your connector "instagram" in backend first.');
        }

        return $connectorEngineConfig;
    }
}
