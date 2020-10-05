<?php

namespace SocialData\Connector\Instagram\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Exception\ClientException;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InstagramController extends AdminController
{
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
            return $this->render('@SocialData/connect-layout.html.twig', [
                'content' => [
                    'error'       => true,
                    'code'        => 500,
                    'identifier'  => 'general_error',
                    'reason'      => 'connector engine configuration error',
                    'description' => $e->getMessage()
                ]
            ]);
        }

        try {
            $redirectUrl = $this->instagramClient->generateRedirectUrl($connectorEngineConfig);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error !== null) {
            return $this->render('@SocialData/connect-layout.html.twig', [
                'content' => [
                    'error'       => true,
                    'code'        => 500,
                    'identifier'  => 'general_error',
                    'reason'      => sprintf('connect error with api type "%s"', $connectorEngineConfig->getApiType()),
                    'description' => $error
                ]
            ]);
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
            return $this->render('@SocialData/connect-layout.html.twig', [
                'content' => [
                    'error'       => true,
                    'code'        => 500,
                    'identifier'  => 'general_error',
                    'reason'      => 'connector engine configuration error',
                    'description' => $e->getMessage()
                ]
            ]);
        }

        try {
            $tokenData = $this->instagramClient->generateAccessTokenFromRequest($connectorEngineConfig, $request);
        } catch (ClientException $e) {
            return $this->render('@SocialData/connect-layout.html.twig', [
                'content' => [
                    'error'       => true,
                    'code'        => $e->getCode(),
                    'identifier'  => $e->getIdentifier(),
                    'reason'      => $e->getReason(),
                    'description' => $e->getMessage()
                ]
            ]);
        }

        if (!is_array($tokenData)) {
            return $this->render('@SocialData/connect-layout.html.twig', [
                'content' => [
                    'error'       => true,
                    'code'        => 500,
                    'identifier'  => 'general_error',
                    'reason'      => 'empty token data',
                    'description' => 'could not generate token data'
                ]
            ]);
        }

        $connectorEngineConfig->setAccessToken($tokenData['token']);
        $connectorEngineConfig->setAccessTokenExpiresAt($tokenData['expiresAt']);
        $this->connectorService->updateConnectorEngineConfiguration('instagram', $connectorEngineConfig);

        return $this->render('@SocialData/connect-layout.html.twig', [
            'content' => [
                'error' => false
            ]
        ]);
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
