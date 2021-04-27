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
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;

/**
 * @author Patrick Landolt <patrick.landolt@artack.ch>
 */
final class DateRangeFilterTest extends FilterTestCase
{
    public function testFilterEmpty(): void
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([null, 'test']));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray(['type' => null, 'value' => []]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => ['start' => null, 'end' => null],
        ]));
        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => ['start' => '', 'end' => ''],
        ]));

        $this->assertSameQuery([], $proxyQuery);
        $this->assertFalse($filter->isActive());
    }

    public function testFilterStartDateAndEndDate(): void
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $startDateTime = new \DateTime('2016-08-01');
        $endDateTime = new \DateTime('2016-08-31');

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => [
                'start' => $startDateTime,
                'end' => $endDateTime,
            ],
        ]));

        $this->assertSameQuery(['WHERE alias.field >= :field_name_0', 'WHERE alias.field <= :field_name_1'], $proxyQuery);
        $this->assertSameQueryParameters([
            'field_name_0' => $startDateTime,
            'field_name_1' => $endDateTime,
        ], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterStartDate(): void
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $startDateTime = new \DateTime('2016-08-01');

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => [
                'start' => $startDateTime,
                'end' => '',
            ],
        ]));

        $this->assertSameQuery(['WHERE alias.field >= :field_name_0'], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_0' => $startDateTime], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    public function testFilterEndDate(): void
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $endDateTime = new \DateTime('2016-08-31');

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => [
                'start' => '',
                'end' => $endDateTime,
            ],
        ]));

        $this->assertSameQuery(['WHERE alias.field <= :field_name_1'], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_1' => $endDateTime], $proxyQuery);
        $this->assertTrue($filter->isActive());
    }

    /**
     * @dataProvider provideDates
     */
    public function testFilterEndDateCoversWholeDay(
        \DateTimeImmutable $expectedEndDateTime,
        \DateTime $viewEndDateTime,
        \DateTimeZone $modelTimeZone
    ): void {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $modelEndDateTime = clone $viewEndDateTime;
        $modelEndDateTime->setTimezone($modelTimeZone);

        $this->assertSame($modelTimeZone->getName(), $modelEndDateTime->getTimezone()->getName());
        $this->assertNotSame($modelTimeZone->getName(), $viewEndDateTime->getTimezone()->getName());

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => [
                'start' => '',
                'end' => $modelEndDateTime,
            ],
        ]));

        $this->assertTrue($filter->isActive());
        $this->assertSameQuery(['WHERE alias.field <= :field_name_1'], $proxyQuery);
        $this->assertSameQueryParameters(['field_name_1' => $modelEndDateTime], $proxyQuery);
        $this->assertSame($expectedEndDateTime->getTimestamp(), $modelEndDateTime->getTimestamp());
    }

    /**
     * @return \Generator<array{\DateTimeImmutable, \DateTime, \DateTimeZone}>
     */
    public function provideDates(): iterable
    {
        yield [
            new \DateTimeImmutable('2016-08-31 23:59:59.0-03:00'),
            new \DateTime('2016-08-31 00:00:00.0-03:00'),
            new \DateTimeZone('UTC'),
        ];

        yield [
            new \DateTimeImmutable('2016-09-01 05:59:59.0-03:00'),
            new \DateTime('2016-08-31 06:00:00.0-03:00'),
            new \DateTimeZone('Antarctica/McMurdo'),
        ];

        yield [
            new \DateTimeImmutable('2016-09-01 06:07:07.0-03:00'),
            new \DateTime('2016-08-31 06:07:08.0-03:00'),
            new \DateTimeZone('Australia/Adelaide'),
        ];

        yield [
            new \DateTimeImmutable('2016-08-31 23:59:59.0-00:00'),
            new \DateTime('2016-08-31 00:00:00.0-00:00'),
            new \DateTimeZone('Pacific/Honolulu'),
        ];

        yield [
            new \DateTimeImmutable('2017-01-01 18:59:59.0+01:00'),
            new \DateTime('2016-12-31 19:00:00.0+01:00'),
            new \DateTimeZone('Africa/Cairo'),
        ];
    }

    public function testFilterEndDateImmutable(): void
    {
        $filter = new DateRangeFilter();
        $filter->initialize('field_name', ['field_options' => ['class' => 'FooBar']]);

        $proxyQuery = new ProxyQuery($this->createQueryBuilderStub());

        $endDateTime = new \DateTimeImmutable('2016-08-31');

        $filter->filter($proxyQuery, 'alias', 'field', FilterData::fromArray([
            'type' => null,
            'value' => [
                'start' => '',
                'end' => $endDateTime,
            ],
        ]));

        $this->assertSameQuery(['WHERE alias.field <= :field_name_1'], $proxyQuery);
        $this->assertTrue($filter->isActive());

        $builder = $proxyQuery->getQueryBuilder();
        \assert($builder instanceof TestQueryBuilder);
        $this->assertCount(1, $builder->queryParameters);
        $this->assertSame(
            $endDateTime->modify('+23 hours 59 minutes 59 seconds')->getTimestamp(),
            $builder->queryParameters['field_name_1']->getTimestamp()
        );
    }
}
