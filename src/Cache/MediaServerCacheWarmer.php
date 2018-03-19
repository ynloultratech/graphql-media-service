<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Cache;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\HttpKernel\KernelEvents;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaServerMetadata;

class MediaServerCacheWarmer extends CacheWarmer implements EventSubscriberInterface
{
    /**
     * @var MediaServerMetadata
     */
    protected $metadata;


    public function __construct(MediaServerMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        $this->metadata->clearCache();
    }

    /**
     * warmUp the cache on request
     * NOTE: this behavior is disabled when debug=false
     *
     * @see \Ynlo\GraphQLMediaServiceBundle\DependencyInjection\MediaServiceExtension::load
     */
    public function warmUpOnEveryRequest()
    {
        $this->warmUp(null);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'warmUpOnEveryRequest',
        ];
    }
}
