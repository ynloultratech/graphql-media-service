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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaService\Model\FileInterface;

class FileManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MediaStorageProviderPool
     */
    protected $providers;

    /**
     * @var array
     */
    protected $config;

    /**
     * FileManager constructor.
     *
     * @param EntityManagerInterface   $em
     * @param MediaStorageProviderPool $providers
     * @param array                    $config
     */
    public function __construct(EntityManagerInterface $em, MediaStorageProviderPool $providers, array $config)
    {
        $this->em = $em;
        $this->providers = $providers;
        $this->config = $config;
    }

    /**
     * @param FileInterface $file
     * @param UploadedFile  $uploadedFile
     *
     * @throws \Exception
     */
    public function saveFile(FileInterface $file, UploadedFile $uploadedFile)
    {
        if (!$file->getStorage()) {
            throw new \LogicException('The file must have a valid storage name to save');
        }

        $this->em->beginTransaction();
        $this->em->persist($file);
        $this->em->flush($file);
        try {
            $this->getStorageProvider($file->getStorage())->save($file, $uploadedFile);
            $this->em->flush($file);
            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        $this->em->refresh($file);
    }

    /**
     * @param FileInterface $file
     *
     * @return \SplFileInfo
     *
     * @throws \Exception
     */
    public function getFile(FileInterface $file)
    {
        if (!$file->getStorage()) {
            throw new \LogicException('The file must have a valid storage name to read');
        }

        return $this->getStorageProvider($file->getStorage())->get($file);
    }

    /**
     * @param FileInterface $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function removeFile(FileInterface $file)
    {
        if (!$file->getStorage()) {
            throw new \LogicException('The file must have a valid storage name to remove');
        }

        return $this->getStorageProvider($file->getStorage())->remove($file);
    }

    /**
     * Create new file instance, must save using saveFile
     *
     * @param string $filePath if a file path is given, then the name,
     *                         size and type is automatically guessed
     *
     * @return FileInterface
     */
    public function newFile($filePath = null): FileInterface
    {
        $class = $this->getFileClass();
        /** @var FileInterface $instance */
        $instance = new $class();

        if ($filePath) {
            $file = new \SplFileInfo($filePath);
            $instance->setName($file->getFilename());
            $instance->setSize($file->getSize());
            $instance->setContentType($file->getMTime());
        }

        $instance->setStorage($this->getDefaultStorageId());

        return $instance;
    }

    /**
     * @return string
     */
    protected function getFileClass(): string
    {
        $class = @$this->config['class'];

        if (!$class) {
            throw new \RuntimeException('The `class` config is required to use the media service.');
        }

        return $class;
    }

    /**
     * @return mixed
     */
    protected function getDefaultStorageId()
    {
        return $this->config['default_storage'];
    }

    /**
     * @param string $storageId
     *
     * @return MediaStorageProviderInterface
     */
    protected function getStorageProvider($storageId)
    {
        return $this->providers->getByStorageId($storageId);
    }
}
