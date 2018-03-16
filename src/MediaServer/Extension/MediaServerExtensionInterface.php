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

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaService\Model\FileInterface;

interface MediaServerExtensionInterface
{
    /**
     * Can alter the file before save
     *
     * @param FileInterface $file
     * @param UploadedFile  $uploadedFile
     *
     * @return UploadedFile|null can return customized uploaded file
     */
    public function preUpload(FileInterface $file, UploadedFile $uploadedFile): void;

    /**
     * This action is triggered when a file is used/assigned to a specific entity/property
     *
     * @param FileInterface       $file
     * @param \ReflectionProperty $property
     */
    public function onUse(FileInterface $file, \ReflectionProperty $property);

    /**
     * This action happen before a file is downloaded.
     * Can be used to return another version of the file
     *
     * NOTE: public files does not trigger this action
     *
     * @param FileInterface $file
     *
     * @return null|\SplFileInfo
     */
    public function preDownload(FileInterface $file);
}
