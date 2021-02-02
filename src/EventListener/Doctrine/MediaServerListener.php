<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\EventListener\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use League\Uri\Uri;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\Extension\MediaServerExtensionInterface;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaServerMetadata;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderPool;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

class MediaServerListener implements EventSubscriber, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var MediaServerMetadata
     */
    protected $metadata;

    /**
     * @var MediaStorageProviderPool
     */
    protected $storageProviders;

    /**
     * @var iterable
     */
    protected $extensions;

    /**
     * @var [] save internal pending changes to avoid a loop and errors
     */
    private $queue;

    /**
     * This flag avoid a infinite loop calling a flush inside another flush.
     *
     * @var bool
     */
    private $flushing = false;

    /**
     * MediaServerListener constructor.
     *
     * @param MediaServerMetadata      $metadata
     * @param MediaStorageProviderPool $storageProviders
     * @param iterable                 $extensions
     */
    public function __construct(MediaServerMetadata $metadata, MediaStorageProviderPool $storageProviders, iterable $extensions = [])
    {
        $this->metadata = $metadata;
        $this->storageProviders = $storageProviders;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad => 'postLoad',
            Events::prePersist => 'prePersist',
            Events::preUpdate => 'preUpdate',
            Events::preRemove => 'preRemove',
            Events::postFlush => 'postFlush',
        ];
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($object instanceof FileInterface) {
            if ($provider = $this->getProviderByStorageId($object->getStorage())) {
                $object->setUrl($this->getDownloadUrl($provider, $object));
            }
        }
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $object = $event->getObject();
        $class = $event->getEntityManager()->getClassMetadata(get_class($object))->name;
        if ($this->metadata->isMappedClass($class)) {
            foreach ($event->getEntityChangeSet() as $name => $changeSet) {
                if ($this->metadata->isMappedProperty($class, $name)) {
                    [, $newValue] = $changeSet;
                    if ($newValue instanceof FileInterface) {
                        $this->attachFileToEntity($newValue, $class, $name);
                    }
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        $class = $event->getEntityManager()->getClassMetadata(get_class($object))->name;
        if ($this->metadata->isMappedClass($class)) {
            $props = $this->metadata->getMappedProperties($class);
            $accessor = new PropertyAccessor();
            foreach ($props as $prop) {
                $value = $accessor->getValue($object, $prop);
                if ($value instanceof FileInterface) {
                    $this->attachFileToEntity($value, $class, $prop);
                }
            }
        }
    }

    public function getDownloadUrl(MediaStorageProviderInterface $provider, FileInterface $file): string
    {
        $uri = $provider->getDownloadUrl($file);
        $url = Uri::createFromString($uri);

        /** @var MediaServerExtensionInterface $extension */
        foreach ($this->extensions as $extension) {
            $newUrl = $extension->downloadUrl($provider, $file, $url);
            if ($newUrl instanceof Uri) {
                $url = $newUrl;
            }
        }

        return (string) $url;
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($object instanceof FileInterface) {
            if ($provider = $this->getProviderByStorageId($object->getStorage())) {
                $provider->remove($object);
            }
        }
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        if (!$this->queue) {
            return;
        }

        foreach ($this->queue as $id => $object) {
            $em = $eventArgs->getEntityManager();
            unset($this->queue[$id]);
            if ($em->contains($object) && !$this->flushing) {
                $this->flushing = true;
                $em->flush($object);
                $this->flushing = false;
            }
        }
    }

    protected function attachFileToEntity(FileInterface $file, $entityClass, $propertyName)
    {
        if ($file->isNew()) {
            $config = $this->metadata->getPropertyConfig($entityClass, $propertyName);

            // When a new file is linked to entity some information will be updated for example
            // sometimes the storage used to upload the file is not the same of the entity one
            // and the file will be moved, renamed etc

            $oldProviderName = $file->getStorage();
            $oldProvider = $this->getProviderByStorageId($oldProviderName);
            $systemFile = $oldProvider->get($file);
            $uploadedFile = new UploadedFile($systemFile, $file->getName(), $file->getContentType(), null, true);

            if ($config->name) {
                $ext = null;
                if (preg_match('/\.\w+$/', $file->getName(), $matches)) {
                    $ext = $matches[0];
                }
                $file->setName(sprintf('%s%s', $config->name, $ext));
            }

            $newProvider = $this->getProviderByStorageId($config->storage);
            $newProvider->save($file, $uploadedFile);

            $file->setUrl($this->getDownloadUrl($newProvider, $file));

            if ($config->storage && $config->storage !== $oldProviderName) {
                $file->setStorage($config->storage);
            }

            // remove in old provider if the provider is different
            if ($oldProviderName !== $file->getStorage()) {
                $oldProvider->remove($file);
            }

            /** @var MediaServerExtensionInterface $extension */
            foreach ($this->extensions as $extension) {
                $provider = $this->getProviderByStorageId($file->getStorage());
                $extension->onUse($provider, $file, new \ReflectionProperty($entityClass, $propertyName));
            }

            $file->used();
            $this->queue($file);
        }
    }

    /**
     * @param string|null $id
     *
     * @return MediaStorageProviderInterface
     */
    protected function getProviderByStorageId($id): MediaStorageProviderInterface
    {
        if (!$id) {
            return $this->storageProviders->getDefaultStorage();
        }

        return $this->storageProviders->getByStorageId($id);
    }
    /**
     * @param object $object
     */
    protected function queue($object)
    {
        $this->queue[spl_object_hash($object)] = $object;
    }
}
