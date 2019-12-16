<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ynlo\GraphQLBundle\Annotation as GraphQL;
use Ynlo\GraphQLBundle\Model\NodeInterface;

abstract class AbstractFile implements FileInterface, NodeInterface
{
    /**
     * @var string
     *
     * @ORM\Id()
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     *
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * Current status of the file
     *
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     *
     * @GraphQL\Exclude()
     */
    protected $status = self::STATUS_NEW;

    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="string", nullable=false)
     *
     * @Assert\NotBlank()
     */
    protected $contentType;

    /**
     * Size in bytes
     *
     * @var int
     *
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    protected $size = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt;

    /**
     * Url to get the file
     *
     * @var string
     *
     * @GraphQL\Field(type="string")
     */
    protected $url;

    /**
     * Storage name used to save the file
     *
     * @var string
     *
     * @ORM\Column(name="storage", type="string", nullable=false)
     *
     * @GraphQL\Exclude()
     */
    protected $storage;

    /**
     * Each storage can save some meta required to recover the file
     *
     * @var array
     *
     * @ORM\Column(name="storage_meta", type="json_array", nullable=true)
     *
     * @GraphQL\Exclude()
     */
    protected $storageMeta = [];

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->name = substr(md5(uniqid(md5(time()), true)), 0, 12);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return AbstractFile
     */
    public function setName($name): AbstractFile
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $mimeType
     *
     * @return AbstractFile
     */
    public function setContentType($mimeType): AbstractFile
    {
        $this->contentType = $mimeType;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size): AbstractFile
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return AbstractFile
     */
    public function setCreatedAt(\DateTime $createdAt): AbstractFile
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return AbstractFile
     */
    public function setUpdatedAt(\DateTime $updatedAt): AbstractFile
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return AbstractFile
     */
    public function setUrl($url): AbstractFile
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return AbstractFile
     */
    public function setStatus(string $status): AbstractFile
    {
        $this->status = $status;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isNew(): bool
    {
        return $this->getStatus() === self::STATUS_NEW;
    }

    /**
     * {@inheritDoc}
     */
    public function isInUse(): bool
    {
        return $this->getStatus() === self::STATUS_IN_USE;
    }

    public function used(): void
    {
        $this->setStatus(self::STATUS_IN_USE);
    }

    /**
     * @return string
     */
    public function getStorage(): ?string
    {
        return $this->storage;
    }

    /**
     * @param string $storage
     *
     * @return AbstractFile
     */
    public function setStorage($storage): AbstractFile
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return array
     */
    public function getStorageMeta(): array
    {
        return $this->storageMeta;
    }

    /**
     * @param array $storageMeta
     *
     * @return AbstractFile
     */
    public function setStorageMeta(array $storageMeta): AbstractFile
    {
        $this->storageMeta = $storageMeta;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setStorageMetaValue($key, $value)
    {
        $this->storageMeta[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getStorageMetaValue($key, $default = null)
    {
        if (isset($this->storageMeta[$key])) {
            return $this->storageMeta[$key];
        }

        return $default;
    }

    /**
     * __toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl() ?: '';
    }
}
