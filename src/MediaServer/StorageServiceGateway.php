<?php

namespace Ynlo\GraphQLMediaServiceBundle\MediaServer;

class StorageServiceGateway
{
    protected MediaStorageProviderInterface $provider;

    protected string $name;

    protected bool $default;

    protected array $config = [];

    public function __construct(string $name, MediaStorageProviderInterface $provider, array $config = [], $default = false)
    {
        $this->config = $config;
        $this->provider = $provider;
        $this->name = $name;
        $this->default = $default;
    }

    /**
     * @return MediaStorageProviderInterface
     */
    public function getProvider(): MediaStorageProviderInterface
    {
        $this->provider->setConfig($this->config);

        return $this->provider;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }
}