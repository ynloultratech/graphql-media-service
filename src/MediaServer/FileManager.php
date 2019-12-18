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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\Extension\MediaServerExtensionInterface;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

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
     * Upload given system file and create new file record or update given
     *
     * @param \SplFileInfo|UploadedFile $systemFile
     * @param FileInterface             $recordFile
     *
     * @return FileInterface
     *
     * @throws \Exception
     */
    public function upload(\SplFileInfo $systemFile, FileInterface $recordFile = null)
    {
        $this->em->beginTransaction();

        if (null === $recordFile) {
            $recordFile = $this->newFile();
        }

        $recordFile->setSize($systemFile->getSize());
        if ($systemFile instanceof FileInterface) {
            $recordFile->setContentType($systemFile->getMimeType());
            $extension = $systemFile->getExtension();
        } else {
            $guesser = MimeTypeGuesser::getInstance();
            $type =  $guesser->guess($systemFile->getPathname());
            $recordFile->setContentType($type);
            $extension = ExtensionGuesser::getInstance()->guess($type);
        }

        // set extension if the name does not have none
        if ($extension && !preg_match('/\.\w+$/', $recordFile->getName())) {
            $extension = preg_replace('/^\./', null, $extension);
            $recordFile->setName(sprintf('%s.%s', $recordFile->getName(), $extension));
        }

        if (!$this->em->contains($recordFile)) {
            $this->em->persist($recordFile);
        }

        $this->em->flush($recordFile);
        try {
            $provider = $this->getStorageProvider($recordFile);
            if ($systemFile instanceof UploadedFile) {
                $uploadFile = $systemFile;
            } else {
                $uploadFile = new UploadedFile($systemFile, $recordFile->getName(), null, null, null, true);
            }

            /** @var MediaServerExtensionInterface $extension */
            foreach ($this->extensions as $extension) {
                $alteredUploadedFile = $extension->preUpload($provider, $recordFile, $uploadFile);
                if ($alteredUploadedFile) {
                    $uploadFile = $alteredUploadedFile;
                }
            }

            $provider->save($recordFile, $uploadFile);
            $this->em->flush($recordFile);
            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        $this->em->refresh($recordFile);

        return $recordFile;
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
     * @return FileInterface
     */
    public function newFile(): FileInterface
    {
        $class = $this->getFileClass();
        /** @var FileInterface $instance */
        $instance = new $class();

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
