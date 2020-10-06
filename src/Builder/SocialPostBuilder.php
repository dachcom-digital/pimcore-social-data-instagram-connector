<?php

namespace SocialData\Connector\Instagram\Builder;

use Carbon\Carbon;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialDataBundle\Exception\BuildException;
use SocialDataBundle\Connector\SocialPostBuilderInterface;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialData\Connector\Instagram\Model\FeedConfiguration;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialDataBundle\Exception\ConnectException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialPostBuilder implements SocialPostBuilderInterface
{
    /**
     * @var InstagramClient
     */
    protected $instagramClient;

    /**
     * @param InstagramClient $instagramClient
     */
    public function __construct(InstagramClient $instagramClient)
    {
        $this->instagramClient = $instagramClient;
    }

    /**
     * {@inheritDoc}
     */
    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(FetchData $data): void
    {
        $options = $data->getOptions();
        $buildConfig = $data->getBuildConfig();

        $engineConfiguration = $buildConfig->getEngineConfiguration();
        $feedConfiguration = $buildConfig->getFeedConfiguration();

        if (!$feedConfiguration instanceof FeedConfiguration) {
            return;
        }

        if (!$engineConfiguration instanceof EngineConfiguration) {
            return;
        }

        try {
            $client = $this->instagramClient->getClient($engineConfiguration);
            $client->setAccessToken($engineConfiguration->getAccessToken());
        } catch (ConnectException $e) {
            throw new BuildException(sprintf('instagram client error: %s', $e->getMessage()));
        }

        $limit = is_numeric($feedConfiguration->getLimit()) ? $feedConfiguration->getLimit() : 50;

        try {
            $response = $client->getUserMedia('me', $limit);
        } catch (\Throwable $e) {
            throw new BuildException(sprintf('fetch error: %s', $e->getMessage()));
        }

        if (!$response instanceof \stdClass) {
            return;
        }

        if (!property_exists($response, 'data')) {
            return;
        }

        $rawItems = $response->data;

        if (!is_array($rawItems)) {
            return;
        }

        if (count($rawItems) === 0) {
            return;
        }

        $items = [];
        foreach ($rawItems as $item) {
            $items[] = get_object_vars($item);
        }

        $data->setFetchedEntities($items);
    }

    /**
     * {@inheritDoc}
     */
    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // nothing to configure so far.
    }

    /**
     * {@inheritDoc}
     */
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

        $socialPost->setContent($element['caption'] ?? null);
        $socialPost->setSocialCreationDate(is_string($element['timestamp']) ? Carbon::create($element['timestamp']) : null);
        $socialPost->setUrl($element['permalink']);
        $socialPost->setPosterUrl($mediaType === 'IMAGE' ? $element['media_url'] : null);

        $data->setTransformedElement($socialPost);
    }
}
