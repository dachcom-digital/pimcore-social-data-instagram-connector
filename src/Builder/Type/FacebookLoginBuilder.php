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

use SocialData\Connector\Instagram\Model\FeedConfiguration;
use SocialData\Connector\Instagram\QueryBuilder\GraphQueryBuilder;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\TransformData;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacebookLoginBuilder extends AbstractBuilder
{
    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        $feedConfiguration = $buildConfig->getFeedConfiguration();
        if (!$feedConfiguration instanceof FeedConfiguration) {
            return;
        }

        parent::configureFetch($buildConfig, $resolver);

        $resolver->setDefault('accountId', $feedConfiguration->getAccountId());
        $resolver->setRequired(['accountId']);
        $resolver->addAllowedTypes('accountId', ['string', 'int']);
    }

    public function transform(TransformData $data): void
    {
        $socialPost = $data->getSocialPostEntity();

        $this->transformPost($data);

        $data->setTransformedElement($socialPost);
    }

    protected function getMediaQueryBuilder(FeedConfiguration $feedConfiguration): GraphQueryBuilder
    {
        return new GraphQueryBuilder(sprintf('%s/media', $feedConfiguration->getAccountId()));
    }
}
