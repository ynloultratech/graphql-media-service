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
use Ynlo\GraphQLMediaService\MediaServer\Extension\MediaServerExtensionInterface;
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
     * @var iterable
     */
    protected $extensions;

    /**
     * FileManager constructor.
     *
     * @param EntityManagerInterface   $em
     * @param MediaStorageProviderPool $providers
     * @param array                    $config
     * @param iterable                 $extensions
     */
    public function __construct(EntityManagerInterface $em, MediaStorageProviderPool $providers, array $config, iterable $extensions = [])
    {
        $this->em = $em;
        $this->providers = $providers;
        $this->config = $config;
        $this->extensions = $extensions;
    }

    /**
     * Upload given real file into file asset and link it
     *
     * @param FileInterface $file
     * @param \SplFileInfo  $realFile
     *
     * @throws \Exception
     */
    public function upload(FileInterface $file, \SplFileInfo $realFile)
    {
        $this->em->beginTransaction();
        $this->em->persist($file);
        $this->em->flush($file);
        try {
            $provider = $this->getStorageProvider($file);
            $uploadFile = new UploadedFile($realFile, $file->getName(), null, null, null, true);

            /** @var MediaServerExtensionInterface $extension */
            foreach ($this->extensions as $extension) {
                $alteredUploadedFile = $extension->preUpload($provider, $file, $uploadFile);
                if ($alteredUploadedFile) {
                    $uploadFile = $alteredUploadedFile;
                }
            }

            $provider->save($file, $uploadFile);
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
    public function get(FileInterface $file)
    {
        return $this->getStorageProvider($file)->get($file);
    }

    /**
     * @param FileInterface $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function remove(FileInterface $file)
    {
        return $this->getStorageProvider($file)->remove($file);
    }

    /**
     * Create new empty file instance, must be persisted using `upload()`
     *
     * @param string $pattern set a real file path to guess name, mime type, size etc.
     *
     * @return FileInterface
     */
    public function createEmptyFile($pattern = null): FileInterface
    {
        $class = $this->getFileClass();
        /** @var FileInterface $instance */
        $instance = new $class();

        if ($pattern) {
            $file = new \SplFileInfo($pattern);
            $instance->setName($file->getFilename());
            $instance->setSize($file->getSize());
            $instance->setContentType($file->getMTime());
        }

        $instance->setStorage($this->getDefaultStorageId());

        return $instance;
    }

    /**
     * @param FileInterface $file
     *
     * @return MediaStorageProviderInterface
     */
    public function getStorageProvider(FileInterface $file)
    {
        if (!$file->getStorage()) {
            throw new \LogicException('The file must have a valid storage.');
        }

        return $this->providers->getByStorageId($file->getStorage());
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
}
