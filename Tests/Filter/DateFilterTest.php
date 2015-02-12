<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class DateFilterTest extends AbstractDateFilterTest
{
    protected function setUp()
    {
        $this->filter = new DateFilter();
        $this->filterName = 'sonata_type_filter_date';
        $this->filterFieldType = 'date';
    }

    /**
     * @test
     * @dataProvider data_single_filter_data
     */
    public function it_should_apply_a_datetime_filter($filterValue, $paramValue, array $options = array())
    {
        $queryBuilder = $this->mockQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('o.bar = :foo_param1');
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('foo_param1', $paramValue);

        $proxyBuilder = $this->mockProxyQueryBuilder($queryBuilder, array('getUniqueParameterId'));
        $proxyBuilder->expects($this->once())
            ->method('getUniqueParameterId')
            ->willReturn('param1');

        $this->filter->initialize('foo', $options);
        $this->filter->filter($proxyBuilder, 'o', 'bar', array('value' => $filterValue));
    }

    public function data_single_filter_data()
    {
        $dt = new \DateTime();
        return array(
            array($dt, $dt),
            array($dt, $dt->getTimestamp(), array('input_type' => 'timestamp')),
        );
    }

    /**
     * @test
     */
    public function it_should_apply_a_date_range_filter_for_datetime_properties()
    {
        $filterValue = new \DateTime();

        $startValue = clone $filterValue;
        $startValue->setTime(0, 0, 0);
        $endValue = clone $startValue;
        $endValue->setTime(23, 59, 59);

        $queryBuilder = $this->mockQueryBuilder();
        $queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                array($this->equalTo('o.bar >= :foo_0')),
                array($this->equalTo('o.bar <= :foo_1'))
            );
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                array($this->equalTo('foo_0'), $this->equalTo($startValue)),
                array($this->equalTo('foo_1'), $this->equalTo($endValue))
            );

        $proxyBuilder = $this->mockProxyQueryBuilder($queryBuilder);

        $this->filter->initialize('foo', array('field_mapping' => array('type' => 'datetime')));
        $this->filter->filter($proxyBuilder, 'o', 'bar', array('value' => $filterValue));
    }

    /**
     * @test
     * @dataProvider data_filter_null_data
     */
    public function it_should_apply_a_null_filter(array $data, $operator, array $options = array())
    {
        $queryBuilder = $this->mockQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with(sprintf('o.bar IS %s', $operator));

        $proxyBuilder = $this->mockProxyQueryBuilder($queryBuilder, array('getUniqueParameterId'));
        $proxyBuilder->expects($this->never())->method('getUniqueParameterId');

        $this->filter->initialize('foo', $options);
        $this->filter->filter($proxyBuilder, 'o', 'bar', $data);
    }

    public function data_filter_null_data()
    {
        return array(
            array(array('value' => null, 'type' => DateType::TYPE_NULL), 'NULL'),
            array(array('value' => null, 'type' => DateType::TYPE_NOT_NULL), 'NOT NULL'),
            array(array('value' => null, 'type' => DateType::TYPE_NOT_NULL), 'NOT NULL', array('input_type' => 'timestamp')),
        );
    }
}