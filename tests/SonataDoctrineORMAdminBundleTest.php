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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class SonataDoctrineORMAdminBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $containerBuilder = new ContainerBuilder();

        $bundle = new SonataDoctrineORMAdminBundle();
        $bundle->build($containerBuilder);

        static::assertNotNull($this->findCompilerPass($containerBuilder, AddGuesserCompilerPass::class));
        static::assertNotNull($this->findCompilerPass($containerBuilder, AddTemplatesCompilerPass::class));
        static::assertNotNull($this->findCompilerPass($containerBuilder, AddAuditEntityCompilerPass::class));
    }

    private function findCompilerPass(ContainerBuilder $container, string $class): ?CompilerPassInterface
    {
        foreach ($container->getCompiler()->getPassConfig()->getPasses() as $pass) {
            if ($pass instanceof $class) {
                return $pass;
            }
        }

        return null;
    }
}
