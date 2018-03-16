<?php

/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Ynlo\GraphQLMediaService\Cache\MediaServerCacheWarmer;
use Ynlo\GraphQLMediaService\Exception\StorageConfigException;

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
            $storage = current($storage);

            if (!@$storage['dir_name']) {
                throw new StorageConfigException($name, '"dir_name" is required.');
            }

            if (!$storage['private'] && !@$storage['base_url']) {
                throw new StorageConfigException($name, '"base_url" is required for public resources.');
            }
        }
    }
}
