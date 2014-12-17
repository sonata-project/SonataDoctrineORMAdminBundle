<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class DateTimeRangeFilterTest extends AbstractDateRangeFilterTest
{
    protected function setUp()
    {
        $this->filter = new DateTimeRangeFilter();
        $this->filterName = 'sonata_type_filter_datetime_range';
        $this->filterFieldType = 'sonata_type_datetime_range';
    }

    public function data_filter_data()
    {
        $dtStart = new \DateTime();
        $dtEnd = new \DateTime();

        return array(
            array(array('value' => array('start' => $dtStart, 'end' => $dtEnd)), $dtStart, $dtEnd),
            array(array('value' => array('start' => $dtStart, 'end' => $dtEnd)),$dtStart->getTimestamp(), $dtEnd->getTimestamp(), array('input_type' => 'timestamp')),
        );
    }

    public function data_incomplete_filter_ranges()
    {
        $dtStart = new \DateTime();
        $dtEnd = new \DateTime();

        return array(
            array(array('value' => array('start' => $dtStart, 'end' => null)), $dtStart, '>='),
            array(array('value' => array('start' => $dtStart, 'end' => false)), $dtStart->getTimestamp(), '>=', array('input_type' => 'timestamp')),
            array(array('value' => array('start' => null, 'end' => $dtEnd)), $dtEnd, '<='),
            array(array('value' => array('start' => false, 'end' => $dtEnd)), $dtEnd->getTimestamp(), '<=', array('input_type' => 'timestamp')),
        );
    }
}