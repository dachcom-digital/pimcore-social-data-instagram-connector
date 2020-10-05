<?php

namespace SocialData\Connector\Instagram;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class SocialDataInstagramConnectorBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    const PACKAGE_NAME = 'dachcom-digital/social-data-instagram-connector';

    /**
     * {@inheritdoc}
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

    /**
     * @return array
     */
    public function getCssPaths()
    {
        return [
            '/bundles/socialdatainstagramconnector/css/admin.css'
        ];
    }

    /**
     * @return string[]
     */
    public function getJsPaths()
    {
        return [
            '/bundles/socialdatainstagramconnector/js/connector/instagram-connector.js',
            '/bundles/socialdatainstagramconnector/js/feed/instagram-feed.js',
        ];
    }
}
