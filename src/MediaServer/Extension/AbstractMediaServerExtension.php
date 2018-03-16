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

abstract class AbstractMediaServerExtension implements MediaServerExtensionInterface
{
    public function preUpload(FileInterface $file, UploadedFile $content): void
    {
        // TODO: Implement preUpload() method.
    }

    public function onUse(FileInterface $file, \ReflectionProperty $property)
    {
        // TODO: Implement onUse() method.
    }

    public function preDownload(FileInterface $file)
    {
        // TODO: Implement preDownload() method.
    }
}
