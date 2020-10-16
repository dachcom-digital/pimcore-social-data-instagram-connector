<?php

namespace SocialData\Connector\Instagram\Builder\Type;

use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialData\Connector\Instagram\Client\InstagramClient;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessBuilder
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
     * @param BuildConfig     $buildConfig
     * @param OptionsResolver $resolver
     */
    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // @todo
    }

    /**
     * @param FetchData $data
     */
    public function fetch(FetchData $data): void
    {
        // @todo
    }

    /**
     * @param BuildConfig     $buildConfig
     * @param OptionsResolver $resolver
     */
    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // @todo
    }

    /**
     * @param FilterData $data
     */
    public function filter(FilterData $data): void
    {
        // @todo
    }

    /**
     * @param BuildConfig     $buildConfig
     * @param OptionsResolver $resolver
     */
    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        // @todo
    }

    /**
     * @param TransformData $data
     */
    public function transform(TransformData $data): void
    {
        // @todo
    }
}
