<?php

/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const STORAGE_LOCAL = 'local';
    const STORAGE_DO_SPACE = 'do_space';
    const STORAGE_CUSTOM = 'custom';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('graphql_media_service');
        $rootNode = $treeBuilder->getRootNode()->addDefaultsIfNotSet()->children();

        $rootNode
            ->scalarNode('class')
            ->info('Entity class to persist and get media files relations')
            ->cannotBeEmpty()
            ->isRequired()
            ->end();

        $rootNode->scalarNode('upload_route')
                 ->defaultValue('media_service_upload')
                 ->cannotBeEmpty()
                 ->info('API end-point to upload files.');

        $rootNode->variableNode('actions')
                 ->defaultValue(['get', 'create', 'update'])
                 ->info(
                     'Allowed actions directly in the media file API end-point. 
                    Can be one or multiple of the following actions,"list", "get", "create","update", "delete"'
                 );

        $rootNode->scalarNode('default_storage')->isRequired()->example('default')->cannotBeEmpty();

        /** @var NodeBuilder $mediaStorage */
        $mediaStorage = $rootNode->arrayNode('storage')
                                 ->info('Media storage to save and fetch media files')
                                 ->useAttributeAsKey('id')
                                 ->isRequired()
                                 ->requiresAtLeastOneElement()
                                 ->prototype('array')
                                 ->children();

        //local storage
        $localStorage = $mediaStorage
            ->arrayNode(self::STORAGE_LOCAL)
            ->info('Provide local storage capabilities, for public or private files')->children();

        $localStorage->booleanNode('private')
                     ->defaultFalse()
                     ->info('Mark this storage as private, otherwise is used as public storage');

        $localStorage->scalarNode('dir_name')
                     ->cannotBeEmpty()
                     ->info(
                         'Absolute local path to store files,
                          NOTE: should be a public accessible path for public assets,
                      and non public accessible path for private assets'
                     )
                     ->example('PRIVATE: "%kernel.project_dir%/media" or PUBLIC: "%kernel.project_dir%/web/media"');

        $localStorage->scalarNode('base_url')
                     ->info('Absolute url to resolve PUBLIC files')
                     ->example('https://example.com/media/');

        $localStorage->scalarNode('route_name')
                     ->defaultValue('media_service_get_file')
                     ->info(
                         'Name of the route to use to resolve PRIVATE assets, 
                     this route will be pre-signed with the configured `signature_parameter`'
                     );

        $localStorage->scalarNode('signature_parameter')
                     ->defaultValue('signature')
                     ->info('Param to set the digital signature');

        $localStorage->scalarNode('expires_parameter')
                     ->defaultValue('expires')
                     ->info('Param to set the signature expiration timestamp');

        $localStorage->integerNode('signature_max_age')
                     ->defaultValue(86400) // 24 hours
                     ->min(1)
                     ->max(31536000) //year
                     ->info('Age in seconds of each signature');


        //local storage
        $localStorage = $mediaStorage
            ->arrayNode(self::STORAGE_DO_SPACE)
            ->info('Use Digital Ocean space as file storage')->children();

        $localStorage->booleanNode('private')
                     ->defaultFalse()
                     ->info('Mark this storage as private, otherwise is used as public storage');

        $localStorage->scalarNode('dir_name')
                     ->cannotBeEmpty()
                     ->info('Directory path to store files');

        $localStorage->scalarNode('accessKey')
                     ->info('Digital Ocean Space access key');

        $localStorage->scalarNode('secretKey')
                     ->info('Digital Ocean Space secret key');

        $localStorage->scalarNode('space')
                     ->info('name of the space to use (must be created on Digital Ocean firstly)');

        $localStorage->scalarNode('region')
                     ->info('region of the namespace to use, MUST match with created namespace region')
                     ->defaultValue('nyc3');

        $localStorage->scalarNode('signature_age')
                     ->info('Age of the signature for private files (ie: 15 minutes, 8 hours, 7 days)')
                     ->defaultValue('15 minutes');

        //service storage
        $serviceStorage = $mediaStorage
            ->arrayNode(self::STORAGE_CUSTOM)
            ->info('Provide third party storage capabilities')->children();

        $serviceStorage->scalarNode('service')
                       ->isRequired()
                       ->info('Name of the service to use');

        $serviceStorage->variableNode('options')
                       ->info('Third party options to pass to the service');

        return $treeBuilder;
    }
}
