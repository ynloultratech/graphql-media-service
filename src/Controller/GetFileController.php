<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ynlo\GraphQLMediaService\MediaServer\LocalMediaStorageProvider;
use Ynlo\GraphQLMediaService\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaService\MediaServer\MediaStorageProviderPool;
use Ynlo\GraphQLMediaService\Model\FileInterface;

class GetFileController extends Controller
{
    public function downloadAction(Request $request)
    {
        //api context is not possible here because its a action out of api
        $class = $this->container->getParameter('media_service_config')['class'];
        $media = $this->container->get('doctrine')->getRepository($class)->findOneBy($request->get('_route_params'));
        if ($media instanceof FileInterface) {
            $provider = $this->getStorageProvider($media->getStorage());

            if (($provider instanceof LocalMediaStorageProvider) && $provider->isValidSignedRequest($media, $request)) {
                return new BinaryFileResponse(
                    new File(
                        $provider->getFileName($media)
                    ),
                    200,
                    [
                        'Content-Type' => $media->getContentType(),
                        'Content-Length' => $media->getSize(),
                    ]
                );
            }

            return new Response(null, Response::HTTP_FORBIDDEN);
        }

        return new Response(null, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param  string $storageId
     *
     * @return MediaStorageProviderInterface
     */
    protected function getStorageProvider($storageId)
    {
        return $this->get(MediaStorageProviderPool::class)->getByStorageId($storageId);
    }
}
