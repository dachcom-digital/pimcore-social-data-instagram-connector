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

namespace SocialData\Connector\Instagram\Builder\Type;

use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessBuilder
{
    protected InstagramClient $instagramClient;

    public function __construct(InstagramClient $instagramClient)
    {
        $this->instagramClient = $instagramClient;
    }

    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // @todo
    }

    public function fetch(FetchData $data): void
    {
        // @todo
    }

    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // @todo
    }

    public function filter(FilterData $data): void
    {
        // @todo
    }

    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // @todo
    }

    public function transform(TransformData $data): void
    {
        // @todo
    }
}
