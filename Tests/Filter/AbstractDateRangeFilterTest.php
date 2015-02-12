<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\AdminBundle\Form\Type\Filter\DateRangeType;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
abstract class AbstractDateRangeFilterTest extends AbstractDateFilterTest
{
    /**
     * @var string
     */
    protected $filterFieldType = 'sonata_type_datetime_range';

    public function data_invalid_filter_data()
    {
        return array(
            // No or invalid data
            array(null),
            array(new \stdClass()),
            array(new \DateTime()),
            array(array()),

            // Invalid values
            array(array('value' => null)),
            array(array('value' => new \stdClass())),
            array(array('value' => array())),
            array(array('value' => array('start' => new \DateTime()))),
            array(array('value' => array('end' => new \DateTime()))),
            array(array('value' => array('start' => null, 'end' => null)))
        );
    }

    /**
     * @test
     * @dataProvider data_incomplete_filter_ranges
     */
    public function it_should_apply_a_date_filter_when_range_is_incomplete($data, $paramValue, $operator, array $options = array())
    {
        $queryBuilder = $this->mockQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with(sprintf('o.bar %s :foo_param1', $operator));
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

    public abstract function data_incomplete_filter_ranges();

    /**
     * @test
     * @dataProvider data_filter_data
     */
    public function it_should_apply_a_datetime_range_filter($data, $startValue, $endValue, array $options = array())
    {
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

        $this->filter->initialize('foo', $options);
        $this->filter->filter($proxyBuilder, 'o', 'bar', $data);
    }

    /**
     * @test
     * @dataProvider data_filter_data
     */
    public function it_should_apply_a_not_within_range_is_incomplete($data, $startValue, $endValue, array $options = array())
    {
        $data['type'] = DateRangeType::TYPE_NOT_BETWEEN;

        $queryBuilder = $this->mockQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('(o.bar < :foo_0 OR o.bar > :foo_1)');
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                array($this->equalTo('foo_0'), $this->equalTo($startValue)),
                array($this->equalTo('foo_1'), $this->equalTo($endValue))
            );

        $proxyBuilder = $this->mockProxyQueryBuilder($queryBuilder);

        $this->filter->initialize('foo', $options);
        $this->filter->filter($proxyBuilder, 'o', 'bar', $data);
    }

    public abstract function data_filter_data();
}