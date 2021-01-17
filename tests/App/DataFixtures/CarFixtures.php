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
use Doctrine\Persistence\ObjectManager;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Car;

final class CarFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $peugeot2000 = new Car('Peugeot', 2000);
        $peugeot2010 = new Car('Peugeot', 2010);
        $ferrari2000 = new Car('Ferrari', 2000);

        $manager->persist($peugeot2000);
        $manager->persist($peugeot2010);
        $manager->persist($ferrari2000);
        $manager->flush();
    }
}
