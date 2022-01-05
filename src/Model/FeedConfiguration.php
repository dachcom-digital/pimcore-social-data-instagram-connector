<?php

namespace SocialData\Connector\Instagram\Model;

use SocialData\Connector\Instagram\Form\Admin\Type\InstagramFeedType;
use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    protected ?int $limit = null;

    public static function getFormClass(): string
    {
        return InstagramFeedType::class;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
