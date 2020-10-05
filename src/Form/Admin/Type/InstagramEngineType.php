<?php

namespace SocialData\Connector\Instagram\Form\Admin\Type;

use SocialData\Connector\Instagram\Model\EngineConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstagramEngineType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('accessToken', TextType::class);
        $builder->add('accessTokenExpiresAt', TextType::class, ['required' => false]);
        $builder->add('appId', TextType::class);
        $builder->add('appSecret', TextType::class);
        $builder->add('apiType', TextType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class'      => EngineConfiguration::class
        ]);
    }
}
