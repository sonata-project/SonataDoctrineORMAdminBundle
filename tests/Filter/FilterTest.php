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

namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;

final class FilterTest extends TestCase
{
    /**
     * @var Filter
     */
    private $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = $this->createFilter();
    }

    public function testFieldDescription(): void
    {
        $this->assertSame(['option1' => 2], $this->filter->getDefaultOptions());
        $this->assertNull($this->filter->getOption('1'));

        $this->filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $this->assertSame(2, $this->filter->getOption('option1'));
        $this->assertNull($this->filter->getOption('foo'));
        $this->assertSame('bar', $this->filter->getOption('foo', 'bar'));

        $this->assertSame('field_name', $this->filter->getName());
        $this->assertSame(['class' => 'FooBar'], $this->filter->getFieldOptions());
    }

    public function testExceptionOnEmptyFieldName(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->filter->getFieldName();
    }

    public function testIsActive(): void
    {
        $this->assertFalse($this->filter->isActive());
    }

    private function createFilter(): Filter
    {
        return new class() extends Filter {
            /**
             * Applies the filter to the QueryBuilder instance.
             *
             * @param string $alias
             * @param string $field
             * @param string $value
             */
            public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value): void
            {
                // TODO: Implement filter() method.
                throw new \BadMethodCallException(sprintf(
                    'Method "%s()" is not implemented.',
                    __METHOD__
                ));
            }

            public function getDefaultOptions(): array
            {
                return ['option1' => 2];
            }

            public function getRenderSettings(): array
            {
                return ['sonata_type_filter_default', [
                    'type' => $this->getFieldType(),
                    'options' => $this->getFieldOptions(),
                ]];
            }
        };
    }
}
