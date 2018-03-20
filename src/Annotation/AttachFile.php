<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Annotation;

/**
 * @Annotation()
 *
 * @Target("PROPERTY")
 */
class AttachFile
{
    /**
     * @var string
     */
    public $storage;

    /**
     * Name of the file to generate without extension
     * e.g. "logo", "profile", "invoice" etc.
     * Otherwise a random name will be generated
     *
     * @var string
     */
    public $name;
}
