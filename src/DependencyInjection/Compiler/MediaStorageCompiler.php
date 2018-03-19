<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderPool;

class MediaStorageCompiler implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(MediaStorageProviderPool::class)) {
            return;
        }

        $definition = $container->getDefinition(MediaStorageProviderPool::class);

        $taggedServices = $container->findTaggedServiceIds('media_service.storage');

        foreach ($taggedServices as $id => $tags) {
            if (isset($tags[0]['alias'])) {
                $definition->addMethodCall('add', [$tags[0]['alias'], new Reference($id)]);
            } else {
                throw new \LogicException('`alias` is required for services tagged as `media_service.storage`');
            }
        }
    }
}
