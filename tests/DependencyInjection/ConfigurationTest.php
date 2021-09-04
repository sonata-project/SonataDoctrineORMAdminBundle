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

namespace Sonata\DoctrineORMAdminBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultOptions(): void
    {
        $config = $this->process([]);

        static::assertNull($config['entity_manager']);
        static::assertTrue($config['audit']['force']);
        static::assertContains(
            '@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig',
            $config['templates']['form']
        );
        static::assertContains(
            '@SonataDoctrineORMAdmin/Form/filter_admin_fields.html.twig',
            $config['templates']['filter']
        );
        static::assertArrayNotHasKey('types', $config['templates']);
    }

    public function testAuditForceWithInvalidFormat(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([[
            'audit' => [
                'force' => '1',
            ],
        ]]);
    }

    public function testCustomTemplates(): void
    {
        $config = $this->process([[
            'templates' => [
                'form' => ['form.twig.html', 'form_extra.twig.html'],
                'filter' => ['filter.twig.html'],
                'types' => [
                    'list' => [
                        'array' => 'list_array.twig.html',
                    ],
                    'show' => [
                        'array' => 'show_array.twig.html',
                    ],
                ],
            ],
        ]]);

        static::assertSame(['form.twig.html', 'form_extra.twig.html'], $config['templates']['form']);
        static::assertSame(['filter.twig.html'], $config['templates']['filter']);
        static::assertSame([
            'list' => [
                'array' => 'list_array.twig.html',
            ],
            'show' => [
                'array' => 'show_array.twig.html',
            ],
        ], $config['templates']['types']);
    }

    public function testTemplateTypesWithInvalidValues(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([[
            'templates' => [
                'types' => [
                    'edit' => [],
                ],
            ],
        ]]);
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process($configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
