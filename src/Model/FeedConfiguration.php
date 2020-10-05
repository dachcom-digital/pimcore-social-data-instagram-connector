<?php

namespace SocialData\Connector\Instagram\Model;

use SocialData\Connector\Instagram\Form\Admin\Type\InstagramFeedType;
use SocialDataBundle\Connector\ConnectorFeedConfigurationInterface;

class FeedConfiguration implements ConnectorFeedConfigurationInterface
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * {@inheritdoc}
     */
    public static function getFormClass()
    {
        return InstagramFeedType::class;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
