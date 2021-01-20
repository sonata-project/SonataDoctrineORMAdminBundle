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
        $foo2000 = new Car('Foo', 2000);
        $foo2010 = new Car('Foo', 2010);
        $bar2000 = new Car('Bar', 2000);

        $manager->persist($foo2000);
        $manager->persist($foo2010);
        $manager->persist($bar2000);
        $manager->flush();
    }
}
