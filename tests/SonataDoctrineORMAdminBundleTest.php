<?php

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
use Prophecy\Argument;
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
    public function testBuild()
    {
        $containerBuilder = $this->prophesize(ContainerBuilder::class);

        $containerBuilder
            ->addCompilerPass(Argument::type(AddGuesserCompilerPass::class))
            ->shouldBeCalledTimes(1);

        $containerBuilder
            ->addCompilerPass(Argument::type(AddTemplatesCompilerPass::class))
            ->shouldBeCalledTimes(1);

        $containerBuilder
            ->addCompilerPass(Argument::type(AddAuditEntityCompilerPass::class))
            ->shouldBeCalledTimes(1);

        $bundle = new SonataDoctrineORMAdminBundle();
        $bundle->build($containerBuilder->reveal());
    }
}
