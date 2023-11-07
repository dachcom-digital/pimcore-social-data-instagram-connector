<?php

namespace SocialData\Connector\Instagram;

use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SocialDataInstagramConnectorBundle extends Bundle
{
    use PackageVersionTrait;

    public const PACKAGE_NAME = 'dachcom-digital/social-data-instagram-connector';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
}
