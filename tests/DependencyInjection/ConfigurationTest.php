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
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultOptions()
    {
        $config = $this->process([]);

        $this->assertNull($config['entity_manager']);
        $this->assertTrue($config['audit']['force']);
        $this->assertArraySubset(['@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig'], $config['templates']['form']);
        $this->assertArraySubset(['@SonataDoctrineORMAdmin/Form/filter_admin_fields.html.twig'], $config['templates']['filter']);
        $this->assertArrayNotHasKey('types', $config['templates']);
    }

    public function testAuditForceWithInvalidFormat()
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([[
            'audit' => [
                'force' => '1',
            ],
        ]]);
    }

    public function testCustomTemplates()
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

        $this->assertSame(['form.twig.html', 'form_extra.twig.html'], $config['templates']['form']);
        $this->assertSame(['filter.twig.html'], $config['templates']['filter']);
        $this->assertSame([
            'list' => [
                'array' => 'list_array.twig.html',
            ],
            'show' => [
                'array' => 'show_array.twig.html',
            ],
        ], $config['templates']['types']);
    }

    public function testTemplateTypesWithInvalidValues()
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
