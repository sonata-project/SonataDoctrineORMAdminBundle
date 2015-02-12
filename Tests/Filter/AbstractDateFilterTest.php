<?php
namespace Sonata\DoctrineORMAdminBundle\Tests\Filter;

use Sonata\DoctrineORMAdminBundle\Filter\AbstractDateFilter;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
abstract class AbstractDateFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractDateFilter
     */
    protected $filter;

    /**
     * @var string
     */
    protected $filterName;

    /**
     * @var string
     */
    protected $filterFieldType = 'datetime';

    /**
     * @test
     * @dataProvider data_invalid_filter_data
     */
    public function it_should_not_filter_when_invalid_data_is_given($data)
    {
        $queryBuilder = $this->getMock('Sonata\AdminBundle\Datagrid\ProxyQueryInterface');
        $queryBuilder->expects($this->never())->method($this->anything());
        $this->filter->filter($queryBuilder, 'o', 'foo', $data);
    }

    public function data_invalid_filter_data()
    {
        return array(
            // No or invalid data
            array(null),
            array(new \stdClass()),
            array(new \DateTime()),
            array(array()),

            // Invalid values
            array(array('value' => new \stdClass())),
            array(array('value' => array()))
        );
    }

    public function testGetRenderSettings()
    {
        $this->assertEquals(
            array(
                $this->filterName,
                array(
                    'field_type'    => $this->filterFieldType,
                    'field_options' => array('required' => false),
                    'label'         => null,
                )
            ),
            $this->filter->getRenderSettings()
        );

        $this->filter->initialize('foo', array(
            'field_type' => 'text',
            'field_options' => array('foo' => 'bar'),
            'label' => 'pink elephants'
        ));
        $this->assertEquals(
            array(
                $this->filterName,
                array(
                    'field_type'    => 'text',
                    'field_options' => array('foo' => 'bar'),
                    'label'         => 'pink elephants',
                )
            ),
            $this->filter->getRenderSettings()
        );
    }

    public function testGetDefaultOptions()
    {
        $this->assertEquals(
            array(
                'input_type' => 'datetime'
            ),
            $this->filter->getDefaultOptions()
        );
    }

    public function testGetFieldType()
    {
        $this->assertEquals($this->filterFieldType, $this->filter->getFieldType());

        $this->filter->initialize('foo', array(
            'field_type' => 'text'
        ));
        $this->assertEquals('text', $this->filter->getFieldType());
    }

    /**
     * @param mixed $queryBuilder
     * @param array|null $methods
     * @return \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockProxyQueryBuilder($queryBuilder, $methods = null)
    {
        return $this->getMockBuilder('Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery')
            ->setMethods($methods)
            ->setConstructorArgs(array($queryBuilder))
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\QueryBuilder
     */
    protected function mockQueryBuilder()
    {
        return $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }
}