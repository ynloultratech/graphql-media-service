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

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaServerMetadata;

class MediaServerCacheWarmer extends CacheWarmer
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
}
