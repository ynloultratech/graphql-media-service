<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\Annotation;

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
}
