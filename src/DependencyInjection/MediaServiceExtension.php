<?php

/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Ynlo\GraphQLMediaServiceBundle\Cache\MediaServerCacheWarmer;
use Ynlo\GraphQLMediaServiceBundle\Exception\StorageConfigException;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\LocalMediaStorageProvider;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\StorageServiceGateway;

class MediaServiceExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->checkProvidersConfig($config);

        $container->setParameter('media_service_config', $config);

        $configDir = __DIR__.'/../Resources/config';
        $loader = new YamlFileLoader($container, new FileLocator($configDir));
        $loader->load('services.yml');

        foreach ($config['storage'] as $name => $storage) {
            $providerName = array_keys($storage)[0];
            switch ($providerName) {
                case Configuration::STORAGE_LOCAL:
                    $service = LocalMediaStorageProvider::class;
                    break;
                default:
                    $service = $storage[$providerName]['service'];
            }
            $options = $storage[$providerName]['options'] ?? [];

            $container->register(sprintf('graphql.storage_service_gateway.%s', $name), StorageServiceGateway::class)
                      ->addArgument($name)
                      ->addArgument(new Reference($service))
                      ->addArgument($options)
                      ->addArgument($name === $config['default_storage'])
                      ->addTag('media_service.storage_service_gateway');
        }

        //in production does not clear cache using request events
        if (!$container->getParameter('kernel.debug')) {
            $container->getDefinition(MediaServerCacheWarmer::class)->clearTag('kernel.event_subscriber');
        }
    }

    public function checkProvidersConfig($config)
    {
        foreach ($config['storage'] as $name => $storage) {
            if (count($storage) > 1) {
                throw new StorageConfigException($name, 'Set only one provider for each storage.');
            }
        }
    }
}
