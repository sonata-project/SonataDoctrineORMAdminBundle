<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Child;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Mother;

final class MotherFixtures extends Fixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $mother = new Mother();
        $this->addChildren($mother, 2);

        $manager->persist($mother);
        $manager->flush();
    }

    private function addChildren(Mother $mother, int $children): void
    {
        for ($i = 0; $i < $children; ++$i) {
            $child = new Child();
            $child->setName('Child '.$i);
            $child->setAnotherName('Another Name '.$i);

            $mother->addChild($child);
        }
    }
}
