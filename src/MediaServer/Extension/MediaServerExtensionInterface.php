<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\MediaServer\Extension;

use League\Uri\Uri;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

interface MediaServerExtensionInterface
{
    /**
     * Can alter the file before save
     *
     * @param MediaStorageProviderInterface $storage
     * @param FileInterface                 $file
     * @param UploadedFile                  $uploadedFile
     *
     * @return UploadedFile|null can return customized uploaded file
     */
    public function preUpload(MediaStorageProviderInterface $storage, FileInterface $file, UploadedFile $uploadedFile): void;

    /**
     * This action is triggered when a file is used/assigned to a specific entity/property
     *
     * @param MediaStorageProviderInterface $storage
     * @param FileInterface                 $file
     * @param \ReflectionProperty           $property
     */
    public function onUse(MediaStorageProviderInterface $storage, FileInterface $file, \ReflectionProperty $property);

    /**
     * Use to alter or add parameters to download url
     *
     * @param MediaStorageProviderInterface $storage
     * @param FileInterface                 $file
     * @param Uri                           $url
     *
     * @return Uri|null return new url instance to use as the url
     */
    public function downloadUrl(MediaStorageProviderInterface $storage, FileInterface $file, Uri $url);

    /**
     * This action happen before a file is downloaded.
     * Can be used to return another version of the file
     *
     * NOTE: public files does not trigger this action
     *
     * @param MediaStorageProviderInterface $storage
     * @param FileInterface                 $file
     * @param Request                       $request
     *
     * @return null|\SplFileInfo
     */
    public function preDownload(MediaStorageProviderInterface $storage, FileInterface $file, Request $request);
}
