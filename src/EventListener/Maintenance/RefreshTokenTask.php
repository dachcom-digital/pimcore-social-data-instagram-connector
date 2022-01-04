<?php

namespace SocialData\Connector\Instagram\EventListener\Maintenance;

use Carbon\Carbon;
use SocialData\Connector\Instagram\Client\InstagramClient;
use Pimcore\Maintenance\TaskInterface;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use SocialDataBundle\Service\LockServiceInterface;

class RefreshTokenTask implements TaskInterface
{
    public const LOCK_ID = 'social_data_instagram_maintenance_task_refresh_token';

    protected LockServiceInterface $lockService;
    protected InstagramClient $instagramClient;
    protected EnvironmentServiceInterface $environmentService;
    protected ConnectorServiceInterface $connectorService;

    public function __construct(
        LockServiceInterface $lockService,
        InstagramClient $instagramClient,
        EnvironmentServiceInterface $environmentService,
        ConnectorServiceInterface $connectorService
    ) {
        $this->lockService = $lockService;
        $this->instagramClient = $instagramClient;
        $this->environmentService = $environmentService;
        $this->connectorService = $connectorService;
    }

    public function execute(): void
    {
        // only run every 6 hours
        $seconds = (int) (6 * 3600);

        if ($this->lockService->isLocked(self::LOCK_ID, $seconds)) {
            return;
        }

        $this->lockService->lock(self::LOCK_ID);

        $connectorDefinition = $this->connectorService->getConnectorDefinition('instagram', true);
        if (!$connectorDefinition->engineIsLoaded()) {
            return;
        }

        /** @var EngineConfiguration $connectorEngineConfig */
        $connectorEngineConfig = $connectorDefinition->getEngineConfiguration();

        // refresh token only works with display api
        if ($connectorEngineConfig->getApiType() !== InstagramClient::API_PRIVATE) {
            return;
        }

        $expiredDate = $connectorEngineConfig->getAccessTokenExpiresAt();

        if (empty($connectorEngineConfig->getAccessToken()) || !$expiredDate instanceof \DateTime) {
            return;
        }

        $now = Carbon::now();
        $dayDiff = $now->diffInDays($expiredDate, false);

        // token expired, we can't refresh it anymore.
        if ($dayDiff <= 0) {
            return;
        }

        // token expires at least in 5 days, we dont need to refresh it now.
        if ($dayDiff > 5) {
            return;
        }

        try {
            $responseData = $this->instagramClient->refreshAccessToken($connectorEngineConfig);
        } catch (\Exception $e) {
            return;
        }

        if (!is_array($responseData)) {
            return;
        }

        $connectorEngineConfig->setAccessToken($responseData['token'], true);
        $connectorEngineConfig->setAccessTokenExpiresAt($responseData['expiresAt'], true);

        $this->connectorService->updateConnectorEngineConfiguration('instagram', $connectorEngineConfig);
    }
}
