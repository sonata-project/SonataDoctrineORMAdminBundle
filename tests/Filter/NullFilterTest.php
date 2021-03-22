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

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\NullFilter;
use Sonata\Form\Type\BooleanType;

final class NullFilterTest extends FilterTestCase
{
    public function testEmpty(): void
    {
        $filter = new NullFilter();
        $filter->initialize('field_name', [
            'field_options' => ['class' => 'FooBar'],
        ]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', []);

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testValue(bool $inverse, int $value, string $expectedQuery): void
    {
        $filter = new NullFilter();
        $filter->initialize('field_name', [
            'field_options' => ['class' => 'FooBar'],
            'inverse' => $inverse,
        ]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());
        $this->assertSameQuery([], $proxyQuery);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => $value]);

        $this->assertSameQuery([$expectedQuery], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testRenderSettings(): void
    {
        $filter = new NullFilter();
        $filter->initialize('field_name', [
            'field_options' => ['class' => 'FooBar'],
        ]);
        $options = $filter->getRenderSettings()[1];

        $this->assertSame(BooleanType::class, $options['field_type']);
    }

    public function valueDataProvider(): array
    {
        return [
            [false, BooleanType::TYPE_YES, 'WHERE alias.field IS NULL'],
            [false, BooleanType::TYPE_NO, 'WHERE alias.field IS NOT NULL'],
            [true, BooleanType::TYPE_YES, 'WHERE alias.field IS NOT NULL'],
            [true, BooleanType::TYPE_NO, 'WHERE alias.field IS NULL'],
        ];
    }
}
