<?php
/**
 * Created by PhpStorm.
 * User: guy_ubun
 * Date: 06/08/18
 * Time: 21:19
 */

namespace OC\PlatformBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OC\PlatformBundle\Entity\Skill;

class LoadSkill implements FixtureInterface
{

    public function load(ObjectManager $manager)
    {

        $names = array('PHP', 'Java', 'C++', 'Symfony', 'Photoshop', 'Blender', 'Excel');

        foreach ($names as $name) {

            $skill = new Skill();
            $skill->setName($name);

            $manager->persist($skill);
        }

        $manager->flush();
    }
}