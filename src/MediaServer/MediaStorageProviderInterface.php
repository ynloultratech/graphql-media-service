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

use Ynlo\GraphQLMediaService\Model\FileInterface;

interface MediaStorageProviderInterface
{
    /**
     * Set resource configuration
     *
     * @param array $config
     *
     * @return mixed
     */
    public function setConfig(array $config);

    /**
     * Read media file
     *
     * @param FileInterface $media
     *
     * @return string file content as string
     *
     * @throws \Exception on fail
     */
    public function read(FileInterface $media);

    /**
     * Resolve the given media and get the url
     *
     * @param FileInterface $media
     */
    public function getDownloadUrl(FileInterface $media);

    /**
     * @param FileInterface $media
     * @param string        $content resource content as string
     *
     * @throws \Exception on fail
     */
    public function save(FileInterface $media, string $content);

    /**
     * Delete the file related to the resource
     *
     * @param FileInterface $media resource containing the file
     *
     * @throws \Exception on fail
     */
    public function remove(FileInterface $media);
}