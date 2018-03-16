<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\MediaServer;

use Ynlo\GraphQLMediaService\DependencyInjection\Configuration;

class MediaStorageProviderPool
{
    /**
     * @var array|MediaStorageProviderInterface[]
     */
    protected $providers;

    /**
     * @var array
     */
    protected $config;

    /**
     * MediaStorageProviderPool constructor.
     *
     * @param array $config media server config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Add new storage provider
     *
     * @param string                        $alias
     * @param MediaStorageProviderInterface $storage
     */
    public function add($alias, MediaStorageProviderInterface $storage)
    {
        $this->providers[$alias] = $storage;
    }

    /**
     * Get default provider for given storage id
     *
     * @param string $storageId get storage provider with settings
     *                          based on configuration defined un the media_server and storage name
     *
     * @return MediaStorageProviderInterface|null
     */
    public function getByStorageId($storageId)
    {
        $config = $this->config['storage'][$storageId];
        foreach (Configuration::STORAGE_PROVIDERS as $providerName) {
            if (isset($config[$providerName])) {
                $provider = clone $this->get($providerName);
                $provider->setConfig($config[$providerName]);//inject current settings

                return $provider;
            }
        }

        return null;
    }

    /**
     * @param string $alias provider alias
     *
     * @return MediaStorageProviderInterface
     */
    protected function get($alias)
    {
        if ($this->has($alias)) {
            return $this->providers[$alias];
        }

        throw new \RuntimeException("Does not exist `$alias` media storage, ensure the service is tagged as `media_service.storage`");
    }

    /**
     * @param string $alias provider alias
     *
     * @return bool
     */
    protected function has($alias)
    {
        return isset($this->providers[$alias]);
    }
}