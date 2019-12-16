<?php

namespace GraphQLMediaServiceDemo\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use GraphQLMediaServiceDemo\App\Entity\Profile;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Ynlo\GraphQLMediaServiceBundle\MediaServer\FileManager;

class AppFixtures extends Fixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $faker;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->faker = Factory::create();
        $this->faker->seed(1);
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $genders = [
            1 => 'male',
            2 => 'male',
            3 => 'male',
            4 => 'male',
            5 => 'female',
            6 => 'female',
            7 => 'female',
            8 => 'female',
            9 => 'female',
            10 => 'male',
        ];

        $i = 0;
        while ($i < 10) {
            $i++;
            $profile = new Profile();
            $profile->setName($this->faker->name($genders[$i]));
            $profile->setEmail($this->faker->email);

            $fileManager = $this->container->get(FileManager::class);

            $avatarImage = __DIR__.DIRECTORY_SEPARATOR.sprintf('avatars/%s.jpg', $i);
            $avatarImageTempUpload = sys_get_temp_dir().DIRECTORY_SEPARATOR.sprintf('avatar-%s.jpg', $i);
            copy($avatarImage, $avatarImageTempUpload);

            $uploadFile = new \SplFileInfo($avatarImageTempUpload);
            $profilePhoto = $fileManager->upload($uploadFile);
            $profile->setPhoto($profilePhoto);

            $licenseImage = __DIR__.DIRECTORY_SEPARATOR.'license.png';
            $licenseImageTempUpload = sys_get_temp_dir().DIRECTORY_SEPARATOR.'license.png';
            copy($licenseImage, $licenseImageTempUpload);

            $uploadFileLicense = new \SplFileInfo($licenseImageTempUpload);
            $license = $fileManager->upload($uploadFileLicense);
            $profile->setLicense($license);

            $manager->persist($profile);
            $this->setReference('profile1', $profile);
        }

        $manager->flush();
    }
}
