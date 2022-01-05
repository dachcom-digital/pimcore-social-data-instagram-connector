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
    protected InstagramClient $instagramClient;
    protected array $typedBuilders;

    public function __construct(InstagramClient $instagramClient)
    {
        $this->typedBuilders = [];
        $this->instagramClient = $instagramClient;
    }

    public function configureFetch(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        $this->dispatch('configureFetch', $engineConfiguration->getApiType(), [$buildConfig, $resolver]);
    }

    public function fetch(FetchData $data): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $data->getBuildConfig()->getEngineConfiguration();

        $this->dispatch('fetch', $engineConfiguration->getApiType(), [$data]);
    }

    public function configureFilter(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        $this->dispatch('configureFilter', $engineConfiguration->getApiType(), [$buildConfig, $resolver]);
    }

    public function filter(FilterData $data): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $data->getBuildConfig()->getEngineConfiguration();

        $this->dispatch('filter', $engineConfiguration->getApiType(), [$data]);
    }

    public function configureTransform(BuildConfig $buildConfig, OptionsResolver $resolver): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $buildConfig->getEngineConfiguration();

        $this->dispatch('configureTransform', $engineConfiguration->getApiType(), [$buildConfig, $resolver]);
    }

    public function transform(TransformData $data): void
    {
        /** @var EngineConfiguration $engineConfiguration */
        $engineConfiguration = $data->getBuildConfig()->getEngineConfiguration();

        $this->dispatch('transform', $engineConfiguration->getApiType(), [$data]);
    }

    /**
     * @throws BuildException
     */
    protected function dispatch(string $method, string $apiType, array $arguments): void
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
