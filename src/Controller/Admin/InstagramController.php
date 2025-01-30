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

namespace SocialData\Connector\Instagram\Controller\Admin;

use Carbon\Carbon;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Connector\ConnectorDefinitionInterface;
use SocialDataBundle\Controller\Admin\Traits\ConnectResponseTrait;
use SocialDataBundle\Exception\ConnectException;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        $this->addInstagramAccounts($connectorEngineConfig);

        $this->connectorService->updateConnectorEngineConfiguration('instagram', $connectorEngineConfig);

        return $this->buildConnectSuccessResponse();
    }

    public function feedConfigAction(Request $request): JsonResponse
    {
        $feedConfig = [
            'accounts' => []
        ];

        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        if ($connectorEngineConfig->hasPages()) {
            $feedConfig['accounts'] = array_values(array_map(static function (array $page) {

                $instagramAccountName = $page['instagramBusinessAccountName'] ?? null;

                if ($instagramAccountName === null) {
                    $instagramAccountName = sprintf('%s (Connected Facebook Page)', $page['facebookPageName'] ?? '--');
                }

                return [
                    'key'   => $instagramAccountName,
                    'value' => $page['instagramBusinessAccountId']
                ];

            }, $connectorEngineConfig->getPages()));
        }

        $feedConfig['apiType'] = $connectorEngineConfig->getApiType();

        return $this->adminJson([
            'success' => true,
            'data'    => $feedConfig
        ]);
    }


    public function debugTokenAction(Request $request): JsonResponse
    {
        try {
            $connectorEngineConfig = $this->getConnectorEngineConfig($this->getConnectorDefinition());
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        if ($connectorEngineConfig->getApiType() !== InstagramClient::API_FACEBOOK_LOGIN) {
            return $this->adminJson(['error' => true, 'message' => 'only facebook tokens can be debugged']);
        }

        $accessToken = $connectorEngineConfig->getAccessToken();

        if (empty($accessToken)) {
            return $this->adminJson(['error' => true, 'message' => 'acccess token is empty']);
        }

        try {
            $accessTokenMetadata = $this->instagramClient->makeCall('/debug_token', 'GET', $connectorEngineConfig, ['input_token' => $accessToken]);
        } catch (\Throwable $e) {
            return $this->adminJson(['error' => true, 'message' => $e->getMessage()]);
        }

        $normalizedData = [];

        if (isset($accessTokenMetadata['data'])) {
            foreach ($accessTokenMetadata['data'] as $rowKey => $rowValue) {
                switch ($rowKey) {
                    case 'expires_at':
                    case 'data_access_expires_at':
                        if ($rowValue === 0) {
                            $normalizedData[$rowKey] = 'Never';
                        } else {
                            $normalizedData[$rowKey] = Carbon::parse($rowValue)->toDayDateTimeString();
                        }

                        break;
                    case 'issued_at':
                        $normalizedData[$rowKey] = Carbon::parse($rowValue)->toDayDateTimeString();

                        break;
                    default:
                        $normalizedData[$rowKey] = $rowValue;
                }
            }
        }

        return $this->adminJson([
            'success' => true,
            'data'    => $normalizedData
        ]);
    }

    protected function addInstagramAccounts(EngineConfiguration $connectorEngineConfig): void
    {
        $connectorEngineConfig->setPages([]);

        if ($connectorEngineConfig->getApiType() !== InstagramClient::API_FACEBOOK_LOGIN) {
            return;
        }

        // now set page tokens
        $pageTokens = $this->instagramClient->makeGraphCall('/me/accounts?fields=name,access_token', $connectorEngineConfig);

        $pages = [];

        //$after = $pageTokens['paging']['cursors']['after'] ?? null;
        //$hasMore = $pageTokens['paging']['next'] ?? false;

        foreach ($pageTokens['data'] ?? [] as $page) {

            $pageData = [
                'facebookPageId'          => $page['id'],
                'facebookPageName'        => $page['name'] ?? null,
                'facebookPageAccessToken' => $page['access_token'] ?? null,
            ];

            $instagramBusinessAccount = $this->instagramClient->makeGraphCall(
                sprintf('/%s?fields=instagram_business_account', $page['id']),
                $connectorEngineConfig
            );

            $instagramBusinessAccountId = $instagramBusinessAccount['instagram_business_account']['id'] ?? null;

            if ($instagramBusinessAccountId === null) {
                continue;
            }

            $instagramBusinessAccountInfo = $this->instagramClient->makeGraphCall(
                sprintf('/%s?fields=name', $instagramBusinessAccountId),
                $connectorEngineConfig
            );

            $pageData['instagramBusinessAccountId'] = $instagramBusinessAccountId;
            $pageData['instagramBusinessAccountName'] = $instagramBusinessAccountInfo['name'] ?? null;

            $pages[] = $pageData;
        }

        $connectorEngineConfig->setPages($pages);
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
