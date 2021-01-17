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
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Sonata\DoctrineORMAdminBundle\Tests\App\Entity\Item;

final class ItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $command1 = $this->getReference(CommandFixtures::COMMAND_1);
        $command2 = $this->getReference(CommandFixtures::COMMAND_2);
        $product1 = $this->getReference(ProductFixtures::PRODUCT_1);
        $product2 = $this->getReference(ProductFixtures::PRODUCT_2);

        $item1 = new Item($command1, $product1);
        $item2 = new Item($command1, $product2);
        $item3 = new Item($command2, $product2);

        $manager->persist($item1);
        $manager->persist($item2);
        $manager->persist($item3);
        $manager->flush();
    }

    /**
     * @phpstan-return class-string[]
     */
    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
            CommandFixtures::class,
        ];
    }
}
