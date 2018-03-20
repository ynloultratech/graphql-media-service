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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Ynlo\GraphQLMediaServiceBundle\Annotation\AttachFile;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

/**
 * Manager to know wish entities and properties are mapped
 * as MediaFile. The mapping information is cached to improve performance
 */
class MediaServerMetadata
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array
     */
    protected $managedEntities = [];

    /**
     * @param Registry $doctrine
     * @param Reader   $reader
     * @param string   $cacheDir
     */
    public function __construct(Registry $doctrine, Reader $reader, $cacheDir)
    {
        $this->doctrine = $doctrine;
        $this->cacheDir = $cacheDir;
        $this->reader = $reader;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function isMappedClass($class)
    {
        return isset($this->getManagedEntities()[$class]);
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getMappedProperties($class)
    {
        return array_keys($this->getManagedEntities()[$class]);
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return bool
     */
    public function isMappedProperty($class, $property)
    {
        return isset($this->getManagedEntities()[$class][$property]);
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return AttachFile
     */
    public function getPropertyConfig($class, $property)
    {
        return $this->getManagedEntities()[$class][$property];
    }

    /**
     * @return array
     */
    public function getManagedEntities(): array
    {
        $this->initialize();

        return $this->managedEntities;
    }

    /**
     * remove the specification cache
     */
    public function clearCache()
    {
        @unlink($this->cacheFileName());
        $this->initialize();
    }

    protected function initialize()
    {
        if (!empty($this->managedEntities)) {
            return;
        }

        $this->loadCache();

        if (!empty($this->managedEntities)) {
            return;
        }

        $meta = $this->doctrine->getManager()->getMetadataFactory()->getAllMetadata();
        /** @var ClassMetadata $m */
        foreach ($meta as $m) {
            $properties = $m->getAssociationNames();
            foreach ($properties as $property) {
                $targetClass = $m->getAssociationTargetClass($property);
                if (is_subclass_of($targetClass, FileInterface::class, true)) {
                    $annotation = $this->reader->getPropertyAnnotation(
                        $m->reflClass->getProperty($property),
                        AttachFile::class
                    );
                    if (!$annotation) {
                        $annotation = new AttachFile();
                    }

                    $this->managedEntities[$m->name][$property] = $annotation;
                }
            }
        }
        $this->saveCache();
    }

    /**
     * @return string
     */
    protected function cacheFileName()
    {
        return $this->cacheDir.DIRECTORY_SEPARATOR.'media_server.meta';
    }

    protected function loadCache()
    {
        if (file_exists($this->cacheFileName())) {
            $content = @file_get_contents($this->cacheFileName());
            if ($content) {
                $this->managedEntities = unserialize($content, [AttachFile::class]);
            }
        }
    }

    protected function saveCache()
    {
        file_put_contents($this->cacheFileName(), serialize($this->managedEntities));
    }
}
