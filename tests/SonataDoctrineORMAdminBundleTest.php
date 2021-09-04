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

namespace Sonata\DoctrineORMAdminBundle\Tests;

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddAuditEntityCompilerPass;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddGuesserCompilerPass;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddTemplatesCompilerPass;
use Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class SonataDoctrineORMAdminBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects(static::exactly(3))->method('addCompilerPass')->withConsecutive(
            [static::isInstanceOf(AddGuesserCompilerPass::class)],
            [static::isInstanceOf(AddTemplatesCompilerPass::class)],
            [static::isInstanceOf(AddAuditEntityCompilerPass::class)]
        );

        $bundle = new SonataDoctrineORMAdminBundle();
        $bundle->build($containerBuilder);
    }
}
