<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Definition;

use Ynlo\GraphQLBundle\Definition\ArgumentDefinition;
use Ynlo\GraphQLBundle\Definition\Loader\DefinitionLoaderInterface;
use Ynlo\GraphQLBundle\Definition\MutationDefinition;
use Ynlo\GraphQLBundle\Definition\Registry\Endpoint;
use Ynlo\GraphQLMediaServiceBundle\Mutation\File\UploadFile;

/**
 * Create dynamically mutation definitions for uploads
 */
class UploadFileDefinitions implements DefinitionLoaderInterface
{
    protected $config;

    /**
     * UploadFileDefinitions constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param Endpoint $endpoint
     */
    public function loadDefinitions(Endpoint $endpoint)
    {
        $mutation = new MutationDefinition();
        $mutation->setName('uploadFile');

        $type = $endpoint->getTypeForClass($this->config['class']);
        $mutation->setType($type);
        $mutation->setResolver(UploadFile::class);

        $argument = new ArgumentDefinition();
        $argument->setName('file');
        $argument->setInternalName('uploadedFile');
        $argument->setType('Upload');

        $mutation->addArgument($argument);
        $endpoint->addMutation($mutation);
    }
}
