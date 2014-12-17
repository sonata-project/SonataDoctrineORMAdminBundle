<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateType;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class TimeFilterTest extends AbstractDateFilterTest
{
    protected function setUp()
    {
        $this->filter = new TimeFilter();
        $this->filterName = 'sonata_type_filter_time';
        $this->filterFieldType = 'time';
    }

    /**
     * @test
     * @dataProvider data_filter_data
     */
    public function it_should_apply_a_datetime_filter(array $data, $paramValue, array $options = array())
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
        $this->filter->filter($proxyBuilder, 'o', 'bar', $data);
    }

    public function data_filter_data()
    {
        $dt = new \DateTime();
        return array(
            array(array('value' => $dt), $dt),
            array(array('value' => $dt), $dt->getTimestamp(), array('input_type' => 'timestamp')),
            array(array('value' => $dt), $dt, array('field_mapping' => array('type' => 'date')))
        );
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