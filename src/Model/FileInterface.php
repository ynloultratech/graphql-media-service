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

use Ynlo\GraphQLBundle\Annotation as GraphQL;

/**
 * @GraphQL\InterfaceType(name="AbstractFile")
 */
interface FileInterface
{
    const STATUS_NEW = 'NEW';
    const STATUS_IN_USE = 'IN_USE';

    /**
     * Unique identifier for resource
     *
     * @GraphQL\Field(type="ID!")
     * @GraphQL\Expose()
     *
     * @return mixed
     */
    public function getId();

    /**
     * Name of the file
     *
     * @GraphQL\Field(type="string!")
     * @GraphQL\Expose()
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Name of the file
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * MimeType of the resource
     *
     * @GraphQL\Field(type="string!")
     * @GraphQL\Expose()
     *
     * @return string
     */
    public function getContentType();

    /**
     * MimeType of the resource
     *
     * @param string $contentType
     */
    public function setContentType($contentType);

    /**
     * Size of the resource in bytes
     *
     * @GraphQL\Field(type="integer!")
     * @GraphQL\Expose()
     *
     * @return int
     */
    public function getSize();

    /**
     * Size of the resource in bytes
     *
     * @param integer $size
     */
    public function setSize($size);

    /**
     * Url to access to the resource
     *
     * @GraphQL\Field(type="string")
     * @GraphQL\Expose()
     *
     * @return string
     */
    public function getUrl();

    /**
     * Url to access to the resource
     *
     * @param string $url
     */
    public function setUrl($url);

    /**
     * @GraphQL\Field(type="datetime!")
     * @GraphQL\Expose()
     *
     * @return \DateTime
     */
    public function getCreatedAt();

    /**
     * @param \DateTime $updated
     */
    public function setCreatedAt(\DateTime $updated);

    /**
     * @GraphQL\Field(type="datetime")
     * @GraphQL\Expose()
     *
     * @return \DateTime
     */
    public function getUpdatedAt();

    /**
     * @param \DateTime $updated
     */
    public function setUpdatedAt(\DateTime $updated);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param string $status
     */
    public function setStatus(string $status);


    /**
     * @return boolean
     */
    public function isNew(): bool;

    /**
     * @return boolean
     */
    public function isInUse(): bool;

    /**
     * @return void
     */
    public function used(): void;

    /**
     * @return string
     */
    public function getStorage(): ?string;

    /**
     * @param string $storage
     */
    public function setStorage($storage);

    /**
     * @param array $meta
     *
     * @return mixed
     */
    public function setStorageMeta(array $meta);

    /**
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    public function setStorageMetaValue($key, $value);

    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    public function getStorageMetaValue($key, $default = null);

    /**
     * @return array
     */
    public function getStorageMeta(): array;
}
