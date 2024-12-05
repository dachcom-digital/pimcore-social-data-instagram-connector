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

namespace SocialData\Connector\Instagram\Builder;

use SocialData\Connector\Instagram\Builder\Type\BusinessBuilder;
use SocialData\Connector\Instagram\Builder\Type\PrivateBuilder;
use SocialData\Connector\Instagram\Client\InstagramClient;
use SocialData\Connector\Instagram\Model\EngineConfiguration;
use SocialDataBundle\Connector\SocialPostBuilderInterface;
use SocialDataBundle\Dto\BuildConfig;
use SocialDataBundle\Dto\FetchData;
use SocialDataBundle\Dto\FilterData;
use SocialDataBundle\Dto\TransformData;
use SocialDataBundle\Exception\BuildException;
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
