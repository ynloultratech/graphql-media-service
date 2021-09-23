<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\MediaServer;

use Spatie\UrlSigner\MD5UrlSigner;
use Spatie\UrlSigner\UrlSigner;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

/**
 * Provide storage capabilities to local files and fetch files using current server
 */
class LocalMediaStorageProvider extends AbstractMediaStorageProvider
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $secret;

    /**
     * LocalMediaStorageProvider constructor.
     *
     * @param RouterInterface $router
     * @param string          $secret
     */
    public function __construct(RouterInterface $router, $secret)
    {
        $this->router = $router;
        $this->secret = $secret;
    }

    /**
     * {@inheritDoc}
     */
    public function get(FileInterface $media): \SplFileInfo
    {
        return new \SplFileInfo($this->getFileName($media));
    }

    /**
     * {@inheritDoc}
     */
    public function getDownloadUrl(FileInterface $media)
    {
        if (!($this->config['private'] ?? false)) {
            return rtrim($this->config['base_url'], '/').'/'.$media->getId().'/'.$media->getName();
        }

        $routeName = $this->config['route_name'] ?? null;
        $route = $this->router->getRouteCollection()->get($routeName);
        preg_match_all('/{(\w+)}/', $route->getPath(), $matches);

        $accessor = new PropertyAccessor();
        $params = [];
        if (isset($matches[1])) {
            foreach ($matches[1] as $paramName) {
                $params[$paramName] = $accessor->getValue($media, $paramName);
            }
        }

        $url = $this->router->generate($routeName, $params, RouterInterface::ABSOLUTE_URL);

        //use domain given in the route
        //symfony by default use current request domain
        if ($domain = $route->getDefault('domain')) {
            $url = preg_replace('/(:\/\/)([^\/])+/', sprintf('$1%s', $domain), $url);
        }

        $expires = \DateTime::createFromFormat('U', time() + $this->config['signature_max_age'] ?? 3600);

        return $this->getUrlSigner($media)->sign($url, $expires);
    }

    /**
     * {@inheritDoc}
     */
    public function save(FileInterface $media, UploadedFile $file)
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->getDirName($media));
        $file->move($this->getDirName($media), $media->getName());

        if ($this->config['private']) {
            $media->setStorageMeta(['salt' => md5(time().mt_rand())]);
        } else {
            $media->setStorageMeta([]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(FileInterface $media)
    {
        if (file_exists($this->getFileName($media))) {
            $fileSystem = new Filesystem();
            $fileSystem->remove($this->getFileName($media));

            // is directory empty
            if (count(scandir($this->getDirName($media))) == 2) {
                $fileSystem->remove($this->getDirName($media));
            }
        }
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
            $this->config['expires_parameter'] ?? 'expires',
            $this->config['signature_parameter'] ?? 'signature'
        );
    }
}
