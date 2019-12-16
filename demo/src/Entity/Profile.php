<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace GraphQLMediaServiceDemo\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ynlo\GraphQLBundle\Annotation as GraphQL;
use Ynlo\GraphQLBundle\Model\NodeInterface;
use Ynlo\GraphQLMediaServiceBundle\Annotation as MediaService;

/**
 * @ORM\Entity()
 * @ORM\Table()
 *
 * @GraphQL\ObjectType()
 *
 * @GraphQL\QueryList()
 * @GraphQL\MutationAdd(options={
 *     @GraphQL\Plugin\Form(type="GraphQLMediaServiceDemo\App\Form\Input\Profile\AddProfileInput")
 * })
 * @GraphQL\MutationUpdate(options={
 *     @GraphQL\Plugin\Form(type="GraphQLMediaServiceDemo\App\Form\Input\Profile\UpdateProfileInput")
 * })
 */
class Profile implements NodeInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Email()
     *
     * @ORM\Column(name="email", type="string")
     */
    protected $email;

    /**
     * @var File
     *
     * @ORM\OneToOne(targetEntity="GraphQLMediaServiceDemo\App\Entity\File", orphanRemoval=true)
     *
     * @GraphQL\Expose()
     *
     * @MediaService\AttachFile(name="photo")
     */
    protected $photo;

    /**
     * @var File
     *
     * @ORM\OneToOne(targetEntity="GraphQLMediaServiceDemo\App\Entity\File", orphanRemoval=true)
     *
     * @GraphQL\Expose()
     *
     * @MediaService\AttachFile(storage="private_files", name="license")
     */
    protected $license;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Profile
     */
    public function setName(string $name): Profile
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Profile
     */
    public function setEmail(string $email): Profile
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getPhoto(): ?File
    {
        return $this->photo;
    }

    /**
     * @param File $photo
     *
     * @return Profile
     */
    public function setPhoto(File $photo): Profile
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getLicense(): ?File
    {
        return $this->license;
    }

    /**
     * @param File $license
     */
    public function setLicense(File $license): void
    {
        $this->license = $license;
    }
}
