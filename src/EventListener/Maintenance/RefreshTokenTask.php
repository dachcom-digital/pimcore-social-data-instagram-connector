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

namespace SocialData\Connector\Instagram\EventListener\Maintenance;

use Carbon\Carbon;
use Pimcore\Maintenance\TaskInterface;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Service\ConnectorServiceInterface;
use SocialDataBundle\Service\EnvironmentServiceInterface;
use SocialDataBundle\Service\LockServiceInterface;

class RefreshTokenTask implements TaskInterface
{
    public const LOCK_ID = 'instagram_maintenance_task_refresh_token';

    public function __construct(
        protected LockServiceInterface $lockService,
        protected InstagramClient $instagramClient,
        protected EnvironmentServiceInterface $environmentService,
        protected ConnectorServiceInterface $connectorService
    ) {
    }

    public function execute(): void
    {
        // only run every 6 hours
        $seconds = (int) (6 * 3600);

        if ($this->lockService->isLocked(self::LOCK_ID)) {
            return;
        }

        $this->lockService->lock(self::LOCK_ID, $seconds);

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

        if (!$expiredDate instanceof \DateTime || empty($connectorEngineConfig->getAccessToken())) {
            return;
        }

        $now = Carbon::now();
        $dayDiff = $now->diffInDays($expiredDate, false);

        // token expired, we can't refresh it anymore.
        if ($dayDiff <= 0) {
            return;
        }

        // token expires at least in 5 days, we don't need to refresh it now.
        if ($dayDiff > 5) {
            return;
        }

        try {
            $refreshedToken = $this->instagramClient->refreshAccessToken($connectorEngineConfig, $connectorEngineConfig->getAccessToken());
        } catch (\Exception $e) {
            return;
        }

        $expiresAt = $refreshedToken->getExpires() !== null ? \DateTime::createFromFormat('U', $refreshedToken->getExpires()) : null;

        $connectorEngineConfig->setAccessToken($refreshedToken->getToken(), true);
        $connectorEngineConfig->setAccessTokenExpiresAt($expiresAt, true);

        $this->connectorService->updateConnectorEngineConfiguration('instagram', $connectorEngineConfig);
    }
}
