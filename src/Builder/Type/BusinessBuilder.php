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
