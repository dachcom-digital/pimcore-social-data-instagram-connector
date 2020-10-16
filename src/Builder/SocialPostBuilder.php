<?php

namespace SocialData\Connector\Instagram\Builder;

use SocialData\Connector\Instagram\Builder\Type\BusinessBuilder;
use SocialData\Connector\Instagram\Builder\Type\PrivateBuilder;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialDataBundle\Exception\BuildException;
use SocialDataBundle\Connector\SocialPostBuilderInterface;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialData\Connector\Instagram\Client\InstagramClient;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialPostBuilder implements SocialPostBuilderInterface
{
    /**
     * @var InstagramClient
     */
    protected $instagramClient;

    /**
     * @var array
     */
    protected $typedBuilders;

    /**
     * @param InstagramClient $instagramClient
     */
    public function __construct(InstagramClient $instagramClient)
    {
        $this->typedBuilders = [];
        $this->instagramClient = $instagramClient;
    }

    /**
     * {@inheritDoc}
     */
    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        $this->dispatch('configureFetch', $engineConfiguration->getApiType(), [$buildConfig, $resolver]);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(FetchData $data): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $data->getBuildConfig()->getEngineConfiguration();

        $this->dispatch('fetch', $engineConfiguration->getApiType(), [$data]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        $this->dispatch('configureFilter', $engineConfiguration->getApiType(), [$buildConfig, $resolver]);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(FilterData $data): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $data->getBuildConfig()->getEngineConfiguration();

        $this->dispatch('filter', $engineConfiguration->getApiType(), [$data]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        $this->dispatch('configureTransform', $engineConfiguration->getApiType(), [$buildConfig, $resolver]);
    }

    /**
     * {@inheritDoc}
     */
    public function transform(TransformData $data): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $data->getBuildConfig()->getEngineConfiguration();

        $this->dispatch('transform', $engineConfiguration->getApiType(), [$data]);
    }

    /**
     * @param string $method
     * @param string $apiType
     * @param array  $arguments
     *
     * @throws BuildException
     */
    protected function dispatch(string $method, string $apiType, array $arguments)
    {
        if (isset($this->typedBuilders[$apiType])) {
            $builder = $this->typedBuilders[$apiType];
        } else {

            $builder = $apiType === InstagramClient::API_PRIVATE
                ? new PrivateBuilder($this->instagramClient)
                : new BusinessBuilder($this->instagramClient);

            $this->typedBuilders[$apiType] = $builder;
        }

        if (!method_exists($builder, $method)) {
            throw new BuildException(sprintf('method "%s" for typed %s builder does not exist', $method, $apiType));
        }

        $builder->$method(...$arguments);
    }
}
