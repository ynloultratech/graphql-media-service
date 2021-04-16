<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\Extension\MediaServerExtensionInterface;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\LocalMediaStorageProvider;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderPool;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

class GetFileController
{
    protected $extensions;

    protected Registry $registry;

    protected $class;

    /**
     * @var MediaStorageProviderPool
     */
    protected $providerPool;

    public function __construct(Registry $registry, array $config, MediaStorageProviderPool $providerPool, iterable $extensions = [])
    {
        $this->extensions = $extensions;
        $this->registry = $registry;
        $this->providerPool = $providerPool;
        $this->class = $config['class'];
    }

    public function __invoke(Request $request)
    {
        //api context is not possible here because its a action out of api
        $id = $request->get('_route_params')['id'] ?? null;
        $name = $request->get('_route_params')['name'] ?? null;

        if ($id && $name) {
            $media = $this->registry->getRepository($this->class)->findOneBy(['id' => $id, 'name' => $name]);
            if ($media instanceof FileInterface) {
                $provider = $this->getStorageProvider($media->getStorage());

                if (($provider instanceof LocalMediaStorageProvider) && $provider->isValidSignedRequest($media, $request)) {
                    $fileToDownload = new File(
                        $provider->getFileName($media)
                    );

                    /** @var MediaServerExtensionInterface $extension */
                    foreach ($this->extensions as $extension) {
                        $newFileToDownload = $extension->preDownload($provider, $media, $request);
                        if ($newFileToDownload) {
                            $fileToDownload = $newFileToDownload;
                        }
                    }

                    return new BinaryFileResponse(
                        $fileToDownload,
                        200,
                        [
                            'Content-Type' => $media->getContentType(),
                            'Content-Length' => $media->getSize(),
                        ]
                    );
                }

                return new Response(null, Response::HTTP_FORBIDDEN);
            }
        }

        return new Response(null, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param string $storageId
     *
     * @return MediaStorageProviderInterface
     */
    protected function getStorageProvider($storageId)
    {
        return $this->providerPool->get($storageId);
    }
}
