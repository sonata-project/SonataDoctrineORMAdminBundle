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

use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util\CallbackClass;
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

        static::assertSame(HiddenType::class, $options['operator_type']);
        static::assertSame([], $options['operator_options']);
    }

    public function testFilterClosure(): void
    {
        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => static function (ProxyQuery $query, string $alias, string $field, FilterData $data): bool {
                $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $query->getQueryBuilder()->setParameter('value', $data->getValue());

                return true;
            },
        ]);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => 'myValue']);

        $this->assertSameQuery(['WHERE CUSTOM QUERY alias.field'], $proxyQuery);
        $this->assertSameQueryParameters(['value' => 'myValue'], $proxyQuery);
        static::assertTrue($filter->isActive());
    }

    public function testFilterMethod(): void
    {
        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => [$this, 'customCallback'],
        ]);

        $filter->filter($proxyQuery, 'alias', 'field', ['value' => 'myValue']);

        $this->assertSameQuery(['WHERE CUSTOM QUERY alias.field'], $proxyQuery);
        $this->assertSameQueryParameters(['value' => 'myValue'], $proxyQuery);
        static::assertTrue($filter->isActive());
    }

    public function customCallback(ProxyQuery $query, string $alias, string $field, FilterData $data): bool
    {
        $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
        $query->getQueryBuilder()->setParameter('value', $data->getValue());

        return true;
    }

    public function testFilterException(): void
    {
        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', []);

        $this->expectException(\RuntimeException::class);
        $filter->filter($proxyQuery, 'alias', 'field', ['value' => 'myValue']);
    }

    public function testApplyMethod(): void
    {
        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name_test', [
            'callback' => static function (ProxyQuery $query, string $alias, string $field, FilterData $data): bool {
                $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $query->getQueryBuilder()->setParameter('value', $data->getValue());

                return true;
            },
            'field_name' => 'field_name_test',
        ]);

        $filter->apply($proxyQuery, ['value' => 'myValue']);

        $this->assertSameQuery(['WHERE CUSTOM QUERY o.field_name_test'], $proxyQuery);
        $this->assertSameQueryParameters(['value' => 'myValue'], $proxyQuery);
        static::assertTrue($filter->isActive());
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @group legacy
     */
    public function testWrongCallbackReturnType(): void
    {
        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();
        $filter->initialize('field_name', [
            'callback' => static function (ProxyQuery $query, string $alias, string $field, FilterData $data): int {
                $query->getQueryBuilder()->andWhere(sprintf('CUSTOM QUERY %s.%s', $alias, $field));
                $query->getQueryBuilder()->setParameter('value', $data->getValue());

                return 1;
            },
        ]);

        $this->expectDeprecation(
            'Using another return type than boolean for the callback option is deprecated'
            .' since sonata-project/doctrine-orm-admin-bundle 3.25 and will throw an exception in version 4.0.'
        );
        $filter->filter($proxyQuery, 'alias', 'field', ['value' => 'myValue']);

        $this->assertSameQuery(['WHERE CUSTOM QUERY alias.field'], $proxyQuery);
        $this->assertSameQueryParameters(['value' => 'myValue'], $proxyQuery);
    }

    /**
     * NEXT_MAJOR: Remove this test.
     *
     * @group legacy
     *
     * @dataProvider provideCallables
     */
    public function testItThrowsDeprecationWithoutFilterData(callable $callable): void
    {
        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter = new CallbackFilter();

        $filter->initialize('field_name_test', [
            'callback' => $callable,
            'field_name' => 'field_name_test',
        ]);

        $this->expectDeprecation('Not adding "Sonata\AdminBundle\Filter\Model\FilterData" as type declaration for argument 4 is deprecated since sonata-project/doctrine-orm-admin-bundle 3.34 and the argument will be a "Sonata\AdminBundle\Filter\Model\FilterData" instance in version 4.0.');

        $filter->apply($proxyQuery, ['value' => 'myValue']);
        static::assertTrue($filter->isActive());
    }

    /**
     * @phpstan-return iterable<array{callable}>
     */
    public function provideCallables(): iterable
    {
        yield 'static class method call' => [[CallbackClass::class, 'staticCallback']];
        yield 'object method call as array' => [[new CallbackClass(), 'callback']];
        yield 'invokable class with array type declaration' => [new CallbackClass()];
        yield 'anonymous function' => [static function ($query, $alias, $field, $data): bool { return true; }];
    }
}
