<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Mutation\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\FileManager;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

/**
 * Upload file mutation resolver
 *
 * @see \Ynlo\GraphQLMediaServiceBundle\Definition\UploadFileDefinitions
 */
class UploadFile
{
    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * UploadFile constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param UploadedFile $uploadedFile
     *
     * @return FileInterface
     *
     * @throws \Exception
     */
    public function __invoke(UploadedFile $uploadedFile)
    {
        return $this->fileManager->upload($uploadedFile);
    }
}
