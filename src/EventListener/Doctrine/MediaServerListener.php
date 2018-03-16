<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\EventListener\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLMediaService\MediaServer\MediaServerMetadata;
use Ynlo\GraphQLMediaService\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaService\MediaServer\MediaStorageProviderPool;
use Ynlo\GraphQLMediaService\Model\FileInterface;

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
     */
    public function __construct(MediaServerMetadata $metadata, MediaStorageProviderPool $storageProviders)
    {
        $this->metadata = $metadata;
        $this->storageProviders = $storageProviders;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postLoad => 'postLoad',
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
                $object->setUrl($provider->getDownloadUrl($object));
            }
        }
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $object = $event->getObject();
        $class = get_class($object);
        if ($this->metadata->isMappedClass($class)) {
            foreach ($event->getEntityChangeSet() as $name => $changeSet) {
                if ($this->metadata->isMappedProperty($class, $name)) {
                    $config = $this->metadata->getPropertyConfig($class, $name);
                    list(, $newValue) = $changeSet;

                    if ($newValue instanceof FileInterface) {
                        $newValue->used();

                        //move from default provider to configured provider
                        if ($config->storage && $newValue->getStorage() !== $config->storage) {
                            $oldProvider = $this->getProviderByStorageId($newValue->getStorage());
                            $file = $oldProvider->get($newValue);

                            $uploadedFile = new UploadedFile($file, $newValue->getName(), $newValue->getContentType(), null, null, true);

                            $newProvider = $this->getProviderByStorageId($config->storage);
                            $newProvider->save($newValue, $uploadedFile);

                            $newValue->setUrl($newProvider->getDownloadUrl($newValue));
                            $newValue->setStorage($config->storage);

                            $oldProvider->remove($newValue);
                        }

                        $this->queue($newValue);
                    }
                }
            }
        }
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

    /**
     * @param string $id
     *
     * @return MediaStorageProviderInterface
     */
    protected function getProviderByStorageId($id)
    {
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
