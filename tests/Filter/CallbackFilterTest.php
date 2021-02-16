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
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class CallbackFilterTest extends FilterTestCase
{
    use ExpectDeprecationTrait;

    public function testRenderSettings(): void
    {
        $filter = new CallbackFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);
        $options = $filter->getRenderSettings()[1];

        $this->assertSame(HiddenType::class, $options['operator_type']);
        $this->assertSame([], $options['operator_options']);
    }

    public function testFilterClosure(): void
    {
        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, array $data): bool {
                $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $query->getQueryBuilder()->setParameter('value', $data['value']);

                return true;
            },
        ]);

        $filter->filter($builder, 'alias', 'field', ['value' => 'myValue']);

        $this->assertSame(['WHERE CUSTOM QUERY alias.field'], $builder->query);
        $this->assertSame(['value' => 'myValue'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterMethod(): void
    {
        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => [$this, 'customCallback'],
        ]);

        $filter->filter($builder, 'alias', 'field', ['value' => 'myValue']);

        $this->assertSame(['WHERE CUSTOM QUERY alias.field'], $builder->query);
        $this->assertSame(['value' => 'myValue'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }

    public function customCallback(ProxyQueryInterface $query, string $alias, string $field, array $data): bool
    {
        $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
        $query->getQueryBuilder()->setParameter('value', $data['value']);

        return true;
    }

    public function testFilterException(): void
    {
        $this->expectException(\RuntimeException::class);

        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', []);

        $filter->filter($builder, 'alias', 'field', ['value' => 'myValue']);
    }

    public function testApplyMethod(): void
    {
        $builder = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name_test', [
            'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, array $data): bool {
                $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $query->getQueryBuilder()->setParameter('value', $data['value']);

                return true;
            },
            'field_name' => 'field_name_test',
        ]);

        $filter->apply($builder, ['value' => 'myValue']);

        $this->assertSame(['WHERE CUSTOM QUERY o.field_name_test'], $builder->query);
        $this->assertSame(['value' => 'myValue'], $builder->queryParameters);
        $this->assertTrue($filter->isActive());
    }
}
