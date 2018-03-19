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

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

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
     * @return \SplFileInfo
     *
     * @throws \Exception on fail
     */
    public function get(FileInterface $media): \SplFileInfo;

    /**
     * Resolve the given media and get the url
     *
     * @param FileInterface $media
     */
    public function getDownloadUrl(FileInterface $media);

    /**
     * @param FileInterface $media
     * @param UploadedFile  $file
     *
     * @throws \Exception on fail
     */
    public function save(FileInterface $media, UploadedFile $file);

    /**
     * Delete the file related to the resource
     *
     * @param FileInterface $media resource containing the file
     *
     * @throws \Exception on fail
     */
    public function remove(FileInterface $media);
}