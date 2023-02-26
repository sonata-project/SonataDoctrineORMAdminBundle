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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;

final class TestEntityManagerFactory
{
    /**
     * @psalm-suppress DeprecatedMethod
     */
    public static function create(): EntityManagerInterface
    {
        if (!\extension_loaded('pdo_sqlite')) {
            TestCase::markTestSkipped('Extension pdo_sqlite is required.');
        }

        if (version_compare(\PHP_VERSION, '8.0.0', '>=')) {
            $config = ORMSetup::createAttributeMetadataConfiguration([], true);
        } else {
            $config = ORMSetup::createAnnotationMetadataConfiguration([], true);
        }

        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            $config
        );

        return new EntityManager(
            $connection,
            $config,
            new EventManager()
        );
    }
}
