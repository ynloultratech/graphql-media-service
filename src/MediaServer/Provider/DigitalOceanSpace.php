<?php

namespace Ynlo\GraphQLMediaServiceBundle\MediaServer\Provider;

use SpacesAPI\Exceptions\FileDoesntExistException;
use SpacesAPI\File;
use SpacesAPI\Space;
use SpacesAPI\Spaces;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\AbstractMediaStorageProvider;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

/**
 * Add support to use digital ocean as provider for file management
 *
 * options:
 *   private: true  // file are marked as private or public
 *   accessKey: {spaceAccessKey}
 *   secretKey: {spaceSecretKey}
 *   space: space-name // name of the space to use
 *   path: // optional path to use to store files, by default in the space root
 *   signature_age: '15 minutes' // age of the signature for private files (default:  15 minutes)
 */
class DigitalOceanSpace extends AbstractMediaStorageProvider
{
    protected ?Space $space = null;

    /**
     * @inheritDoc
     */
    public function get(FileInterface $media): \SplFileInfo
    {
        $file = $this->resolveFile($media);

        $tmpFile = tempnam(sys_get_temp_dir(), 'media');
        if ($file) {
            $file->download($tmpFile);

            return new \SplFileInfo($tmpFile);
        }

        throw new FileDoesntExistException();
    }

    /**
     * @inheritDoc
     */
    public function save(FileInterface $media, UploadedFile $file)
    {
        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.mt_rand().$media->getName();
        copy($file->getRealPath(), $tempFile);
        $path = isset($this->config['path']) && $this->config['path'] ? sprintf('%s/', $this->config['path']) : null;
        $spaceFile = $this->getSpace()->uploadFile($tempFile, sprintf('%s%s/%s', $path, $media->getId(), $media->getName()));

        if ($spaceFile) {
            if ($this->config['private'] ?? false) {
                $spaceFile->makePrivate();
            } else {
                $spaceFile->makePublic();
            }

            $media->setStorageMeta([
                'filename' => $spaceFile->filename,
                'url' => $spaceFile->getURL(),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(FileInterface $media)
    {
        $file = $this->resolveFile($media);
        if ($file) {
            $file->delete();
            if (empty($this->getSpace()->listFiles($media->getId()))) {
                $path = isset($this->config['path']) && $this->config['path'] ? sprintf('%s/', $this->config['path']) : null;
                $this->getSpace()->deleteDirectory(sprintf('%s%s', $path, $media->getId()));
            }
        }
    }

    public function getDownloadUrl(FileInterface $media)
    {
        $file = $this->resolveFile($media);

        if ($file) {
            if ($file->isPublic()) {
                if ($url = $media->getStorageMetaValue('url')) {
                    return $url;
                }

                return $file->getURL();
            }

            return $file->getSignedURL($this->config['signature_age'] ?? '15 minutes');
        }

        return '';
    }

    protected function getSpace(): Space
    {
        if (!$this->space || $this->space->getName() !== $this->config['space']) {
            $spaces = new Spaces($this->config['accessKey'], $this->config['secretKey'], $this->config['region'] ?? 'nyc3');
            $this->space = $spaces->space($this->config['space']);
        }

        return $this->space;
    }

    protected function resolveFile(FileInterface $media): ?File
    {
        $fileName = $media->getStorageMetaValue('filename') ?? null;

        if ($fileName) {
            return $this->getSpace()->file($media->getStorageMetaValue('filename'));
        }

        return null;
    }
}