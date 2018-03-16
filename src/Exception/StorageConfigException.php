<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\Exception;

class StorageConfigException extends \Exception
{
    public function __construct($configName, $message)
    {
        parent::__construct(sprintf('The storage config `%s` is not valid. %s', $configName, $message));
    }
}
