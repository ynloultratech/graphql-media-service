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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Ynlo\GraphQLBundle\Definition\Registry\DefinitionRegistry;
use Ynlo\GraphQLBundle\Model\ID;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\FileManager;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderInterface;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\MediaStorageProviderPool;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

class UploadFileController extends Controller
{
    public function uploadAction(Request $request)
    {
        $fm = $this->get(FileManager::class);
        $file = $fm->createEmptyFile();

        $content = $request->getContent();
        $contentType = $request->headers->get('content-type');
        $contentLength = $request->headers->get('content-length');
        $name = $request->get('name', $file->getName());

        if (!$content
            || !$contentType
            || !$contentLength
            || !$name
        ) {
            throw new BadRequestHttpException();
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        $handle = fopen($tmpFile, 'wb+');
        fwrite($handle, $content);
        fclose($handle);

        $file->setName($name);
        $file->setContentType($contentType);
        $file->setSize($contentLength);
        $file->setUpdatedAt(new \DateTime());

        $fm->upload($file, new \SplFileInfo($tmpFile));

        $type = $this->container
            ->get(DefinitionRegistry::class)
            ->getEndpoint()
            ->getTypeForClass($this->getFileClass());

        return new JsonResponse(
            [
                'data' => [
                    'id' => ID::encode($type, $file->getId()),
                ],
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * @return FileInterface
     */
    protected function newFile(): FileInterface
    {
        $class = $this->getFileClass();

        return new $class();
    }

    /**
     * @return string
     */
    protected function getFileClass(): string
    {
        if (!$this->container->hasParameter('media_service_config')) {
            throw new \RuntimeException('Can`t find a valid config for media service. Ensure you have the bundle enabled.');
        }

        $class = @$this->getParameter('media_service_config')['class'];

        if (!$class) {
            throw new \RuntimeException('The `class` config is required to use the media service.');
        }

        return $class;
    }

    /**
     * @return mixed
     */
    protected function getDefaultStorageId()
    {
        return $this->getParameter('media_service_config')['default_storage'];
    }

    /**
     * @param string $storageId
     *
     * @return MediaStorageProviderInterface
     */
    protected function getStorageProvider($storageId)
    {
        return $this->get(MediaStorageProviderPool::class)->getByStorageId($storageId);
    }
}
