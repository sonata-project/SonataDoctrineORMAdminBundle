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

namespace Sonata\DoctrineORMAdminBundle\Tests\Functional;

use Sonata\DoctrineORMAdminBundle\Tests\App\AppKernel;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

// TODO: Simplify this once Symfony Panther supports Symfony 6
if (class_exists(PantherTestCase::class)) {
    abstract class BCPantherTestCase extends PantherTestCase
    {
    }
} else {
    abstract class BCPantherTestCase
    {
    }
}

abstract class BasePantherTestCase extends BCPantherTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        if (!class_exists(PantherTestCase::class)) {
            static::markTestSkipped('Symfony Panther is not compatible with Symfony 6');
        }

        $this->client = static::createPantherClient([
            'browser' => PantherTestCase::CHROME,
            'connection_timeout_in_ms' => 5000,
            'request_timeout_in_ms' => 60000,
        ]);
    }

    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }
}
