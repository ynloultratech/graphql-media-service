<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\MediaServer\Extension;

use League\Url\Url;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Ynlo\GraphQLMediaService\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaService\Model\FileInterface;

abstract class AbstractMediaServerExtension implements MediaServerExtensionInterface
{
    public function preUpload(MediaStorageProviderInterface $storage, FileInterface $file, UploadedFile $uploadedFile): void
    {
        // TODO: Implement preUpload() method.
    }

    public function onUse(MediaStorageProviderInterface $storage, FileInterface $file, \ReflectionProperty $property)
    {
        // TODO: Implement onUse() method.
    }

    public function downloadUrl(MediaStorageProviderInterface $storage, FileInterface $file, Url $url)
    {
        // TODO: Implement downloadUrl() method.
    }

    public function preDownload(MediaStorageProviderInterface $storage, FileInterface $file, Request $request)
    {
        // TODO: Implement preDownload() method.
    }
}
