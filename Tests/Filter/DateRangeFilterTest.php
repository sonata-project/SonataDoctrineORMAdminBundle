<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class DateRangeFilterTest extends AbstractDateRangeFilterTest
{
    protected function setUp()
    {
        $this->filter = new DateRangeFilter();
        $this->filterName = 'sonata_type_filter_date_range';
        $this->filterFieldType = 'sonata_type_date_range';
    }

    public function data_filter_data()
    {
        $dt = new \DateTime();
        $dtStart = clone $dt;
        $dtStart->setTime(0, 0, 0);
        $dtEnd = clone $dt;
        $dtEnd->setTime(23, 59 ,59);

        return array(
            array(array('value' => array('start' => $dt, 'end' => $dt)), $dtStart, $dtEnd),
            array(array('value' => array('start' => $dt, 'end' => $dt)), $dtStart->getTimestamp(), $dtEnd->getTimestamp(), array('input_type' => 'timestamp')),
        );
    }

    public function data_incomplete_filter_ranges()
    {
        $dt = new \DateTime();
        $dtStart = clone $dt;
        $dtStart->setTime(0, 0, 0);
        $dtEnd = clone $dt;
        $dtEnd->setTime(23, 59 ,59);

        return array(
            array(array('value' => array('start' => $dt, 'end' => null)), $dtStart, '>='),
            array(array('value' => array('start' => $dt, 'end' => false)), $dtStart->getTimestamp(), '>=', array('input_type' => 'timestamp')),
            array(array('value' => array('start' => null, 'end' => $dt)), $dtEnd, '<='),
            array(array('value' => array('start' => false, 'end' => $dt)), $dtEnd->getTimestamp(), '<=', array('input_type' => 'timestamp')),
        );
    }
}