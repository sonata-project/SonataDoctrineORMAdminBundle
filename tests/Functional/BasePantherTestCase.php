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

abstract class BasePantherTestCase extends PantherTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = static::createPantherClient([
            'browser' => PantherTestCase::FIREFOX,
            'connection_timeout_in_ms' => 5000,
            'request_timeout_in_ms' => 60000,
        ]);
    }

    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }
}
