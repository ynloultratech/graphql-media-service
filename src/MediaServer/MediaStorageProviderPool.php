<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\MediaServer;

class MediaStorageProviderPool
{
    /**
     * @var array|StorageServiceGateway[]
     */
    protected array $storages = [];

    /**
     * @param iterable|StorageServiceGateway[] $storages
     */
    public function __construct(iterable $storages)
    {
        foreach ($storages as $storage) {
            $this->storages[$storage->getName()] = $storage;
        }
    }

    /**
     * Get default provider for given storage id
     *
     * @param string|null $name      get storage provider with settings
     *                               based on configuration defined un the media_server and storage name
     *
     * @return MediaStorageProviderInterface
     */
    public function get(string $name = null): MediaStorageProviderInterface
    {
        if (!$name) {
            return $this->getDefault();
        }

        if (isset($this->storages[$name])) {
            return $this->storages[$name]->getProvider();
        }

        throw new \LogicException(sprintf('There are not a storage provider called %s', $name));
    }

    public function getDefault(): MediaStorageProviderInterface
    {
        foreach ($this->storages as $storage) {
            if ($storage->isDefault()) {
                return $storage->getProvider();
            }
        }

        throw new \LogicException('There are not a default storage');
    }

    /**
     * @param string $name provider alias
     *
     * @return bool
     */
    protected function has($name)
    {
        return isset($this->providers[$name]);
    }
}