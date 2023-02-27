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

namespace Sonata\DoctrineORMAdminBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Compiler\AddTemplatesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AddTemplatesCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testDefaultBehavior(): void
    {
        $admin = new Definition(null);
        $admin->addMethodCall('setFilterTheme', [['custom_call.twig.html']]);
        $admin->addTag('sonata.admin', ['manager_type' => 'orm']);

        $this->setDefinition('my.admin', $admin);

        $this->compile();

        static::assertContainerBuilderHasServiceDefinitionWithMethodCall('my.admin', 'setFilterTheme', [['@SonataDoctrineORMAdmin/Form/filter_admin_fields.html.twig', 'custom_call.twig.html']]);
        static::assertContainerBuilderHasServiceDefinitionWithMethodCall('my.admin', 'setFormTheme', [['@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig']]);
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AddTemplatesCompilerPass());
	}
}
