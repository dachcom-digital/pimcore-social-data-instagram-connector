<?php

namespace SocialData\Connector\Instagram\Model;

use SocialData\Connector\Instagram\Form\Admin\Type\InstagramEngineType;
use SocialDataBundle\Connector\ConnectorEngineConfigurationInterface;

class EngineConfiguration implements ConnectorEngineConfigurationInterface
{
    /**
     * @internal
     */
    protected ?string $accessToken = null;

    /**
     * @internal
     */
    protected ?\DateTime $accessTokenExpiresAt = null;

    protected ?string $appId;
    protected ?string $appSecret;
    protected ?string $apiType;

    public static function getFormClass(): string
    {
        return InstagramEngineType::class;
    }

    public function setAccessToken(?string $token, bool $forceUpdate = false): void
    {
        // symfony: if there are any fields on the form that are not included in the submitted data,
        // those fields will be explicitly set to null.
        if ($token === null && $forceUpdate === false) {
            return;
        }

        $this->accessToken = $token;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessTokenExpiresAt(?\DateTime $expiresAt, bool $forceUpdate = false): void
    {
        // symfony: if there are any fields on the form that are not included in the submitted data,
        // those fields will be explicitly set to null.
        if ($expiresAt === null && $forceUpdate === false) {
            return;
        }

        $this->accessTokenExpiresAt = $expiresAt;
    }

    public function getAccessTokenExpiresAt(): ?\DateTime
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppSecret(string $appSecret): void
    {
        $this->appSecret = $appSecret;
    }

    public function getAppSecret(): ?string
    {
        return $this->appSecret;
    }

    public function setApiType(string $apiType): void
    {
        $this->apiType = $apiType;
    }

    public function getApiType(): ?string
    {
        return $this->apiType;
    }
}
