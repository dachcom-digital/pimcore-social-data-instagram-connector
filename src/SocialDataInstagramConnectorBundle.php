<?php

namespace SocialData\Connector\Instagram;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class SocialDataInstagramConnectorBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/social-data-instagram-connector';

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/socialdatainstagramconnector/css/admin.css'
        ];
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/socialdatainstagramconnector/js/connector/instagram-connector.js',
            '/bundles/socialdatainstagramconnector/js/feed/instagram-feed.js',
        ];
    }
}
