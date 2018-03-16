<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLBundle\Demo\AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Ynlo\GraphQLMediaService\Demo\AppBundle\Entity\Profile;

/**
 * Class FixtureManager
 */
class Fixtures extends Fixture
{
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
        $profile = new Profile();
        $profile->setName($this->faker->name);
        $profile->setEmail($this->faker->email);
        $manager->persist($profile);
        $this->setReference('profile1', $profile);

        $manager->flush();
    }
}
