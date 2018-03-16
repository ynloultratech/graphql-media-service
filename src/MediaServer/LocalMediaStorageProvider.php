<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaService\MediaServer;

use Spatie\UrlSigner\MD5UrlSigner;
use Spatie\UrlSigner\UrlSigner;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Router;
use Ynlo\GraphQLMediaService\Model\FileInterface;

/**
 * Provide storage capabilities to local files and fetch files using current server
 */
class LocalMediaStorageProvider extends AbstractMediaStorageProvider
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string
     */
    protected $secret;

    /**
     * LocalMediaStorageProvider constructor.
     *
     * @param Router $router
     * @param string $secret
     */
    public function __construct(Router $router, $secret)
    {
        $this->router = $router;
        $this->secret = $secret;
    }

    /**
     * {@inheritDoc}
     */
    public function read(FileInterface $media)
    {
        $handle = fopen($this->getFileName($media), 'rb+');

        return fread($handle, filesize($this->getFileName($media)));
    }

    /**
     * {@inheritDoc}
     */
    public function getDownloadUrl(FileInterface $media)
    {
        if (!$this->config['private']) {
            return rtrim($this->config['base_url'], '/').'/'.$media->getId().'/'.$media->getName();
        }

        $routeName = $this->config['route_name'];
        $route = $this->router->getRouteCollection()->get($routeName);
        preg_match_all('/{(\w+)}/', $route->getPath(), $matches);

        $accessor = new PropertyAccessor();
        $params = [];
        if (isset($matches[1])) {
            foreach ($matches[1] as $paramName) {
                $params[$paramName] = $accessor->getValue($media, $paramName);
            }
        }

        $url = $this->router->generate($routeName, $params, Router::ABSOLUTE_URL);

        $expires = \DateTime::createFromFormat('U', time() + $this->config['signature_max_age']);

        return $this->getUrlSigner($media)->sign($url, $expires);
    }

    /**
     * {@inheritDoc}
     */
    public function save(FileInterface $media, string $content)
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->getDirName($media));
        $handle = fopen($this->getFileName($media), 'wb+');
        if ($this->config['private']) {
            $media->setStorageMeta(['salt' => md5(time().mt_rand())]);
        } else {
            $media->setStorageMeta([]);
        }

        fwrite($handle, $content);
        fclose($handle);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(FileInterface $media)
    {
        $fileSystem = new Filesystem();

        $fileSystem->remove($this->getDirName($media));
    }

    /**
     * @param FileInterface $media
     *
     * @return string
     */
    public function getDirName(FileInterface $media)
    {
        return $this->config['dir_name'].'/'.$media->getId();
    }

    /**
     * @param FileInterface $media
     *
     * @return string
     */
    public function getFileName(FileInterface $media)
    {
        return $this->getDirName($media).'/'.$media->getName();
    }

    /**
     * @param FileInterface $media
     * @param Request       $request
     *
     * @return bool
     */
    public function isValidSignedRequest(FileInterface $media, Request $request)
    {
        $uri = $request->getUri();

        return $this->getUrlSigner($media)->validate($uri);
    }

    /**
     * @param FileInterface $media
     *
     * @return UrlSigner
     *
     * @throws \Spatie\UrlSigner\Exceptions\InvalidSignatureKey
     */
    protected function getUrlSigner(FileInterface $media): UrlSigner
    {
        return new MD5UrlSigner(
            $media->getStorageMetaValue('salt', $media->getId()),
            $this->config['expires_parameter'],
            $this->config['signature_parameter']
        );
    }
}
