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

use Carbon\Carbon;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialData\Connector\Instagram\Model\FeedConfiguration;
use SocialData\Connector\Instagram\QueryBuilder\InstagramQueryBuilder;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialDataBundle\Exception\BuildException;
use SocialDataBundle\Exception\ConnectException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrivateBuilder
{
    protected InstagramClient $instagramClient;

    public function __construct(InstagramClient $instagramClient)
    {
        $this->instagramClient = $instagramClient;
    }

    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        $engineConfiguration = $buildConfig->getEngineConfiguration();
        $feedConfiguration = $buildConfig->getFeedConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        if (!$feedConfiguration instanceof FeedConfiguration) {
            return;
        }

        $limit = is_numeric($feedConfiguration->getLimit()) ? $feedConfiguration->getLimit() : 50;

        $igQueryBuilder = new InstagramQueryBuilder('media');

        $childrenEdgeFields = $igQueryBuilder
            ->edge('children')
            ->fields([
                'id',
                'media_type',
                'media_url',
                'permalink',
                'thumbnail_url',
                'timestamp',
                'username'
            ]);

        $igQueryBuilder
            ->limit($limit)
            ->fields($childrenEdgeFields)
            ->fields([
                'id',
                'caption',
                'media_type',
                'media_url',
                'permalink',
                'thumbnail_url',
                'timestamp',
                'username'
            ]);

        $resolver->setDefaults([
            'instagramQueryBuilder' => $igQueryBuilder
        ]);

        $resolver->setRequired(['instagramQueryBuilder']);
        $resolver->addAllowedTypes('instagramQueryBuilder', [InstagramQueryBuilder::class]);
    }

    /**
     * @throws BuildException
     */
    public function fetch(FetchData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        /** @var InstagramQueryBuilder $fqbRequest */
        $fqbRequest = $options['instagramQueryBuilder'];
        $query = $fqbRequest->asEndpoint();

        try {
            $response = $this->instagramClient->makeGraphCall($query, $engineConfiguration);
        } catch (ConnectException $e) {
            throw new BuildException(sprintf('graph error: %s [endpoint: %s]', $e->getMessage(), $query));
        }

        if (!is_array($response['data'])) {
            return;
        }

        $rawItems = $response['data'];

        if (count($rawItems) === 0) {
            return;
        }

        $data->setFetchedEntities($rawItems);
    }

    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    public function filter(FilterData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $element = $data->getTransferredData();

        if (!is_array($element)) {
            return;
        }

        // @todo: check if feed has some filter (filter for hashtag for example)

        $data->setFilteredElement($element);
        $data->setFilteredId($element['id']);
    }

    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    public function transform(TransformData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $element = $data->getTransferredData();
        $socialPost = $data->getSocialPostEntity();

        if (!is_array($element)) {
            return;
        }

        $mediaType = $element['media_type'];
        $posterUrl = in_array($mediaType, ['IMAGE', 'CAROUSEL_ALBUM']) ? $element['media_url'] : ($mediaType === 'VIDEO' ? $element['thumbnail_url'] : null);

        $socialPost->setContent($element['caption'] ?? null);
        $socialPost->setSocialCreationDate(is_string($element['timestamp']) ? Carbon::create($element['timestamp']) : null);
        $socialPost->setUrl($element['permalink']);
        $socialPost->setPosterUrl($posterUrl);

        $data->setTransformedElement($socialPost);
    }
}
