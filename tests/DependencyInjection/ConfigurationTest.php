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

final class ConfigurationTest extends TestCase
{
    public function testDefaultOptions(): void
    {
        $config = $this->process([]);

        $this->assertArrayHasKey('entity_manager', $config);
        $this->assertNull($config['entity_manager']);

        $this->assertArrayHasKey('audit', $config);
        $this->assertIsArray($config['audit']);
        $this->assertArrayHasKey('force', $config['audit']);
        $this->assertTrue($config['audit']['force']);

        $this->assertArrayHasKey('templates', $config);
        $this->assertIsArray($config['templates']);
        $this->assertArrayNotHasKey('types', $config['templates']);
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

        $this->assertArrayHasKey('templates', $config);
        $this->assertIsArray($config['templates']);
        $this->assertArrayHasKey('types', $config['templates']);
        $this->assertSame([
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
     * @param mixed[] $configs An array of raw configurations
     *
     * @return mixed[] A normalized array
     */
    protected function process($configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
