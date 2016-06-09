<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Builder;

use Sonata\AdminBundle\Datagrid\Pager;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DatagridBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TypeGuesserInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $typeGuesser;

    /**
     * @var DatagridBuilder
     */
    protected $datagridBuilder;
    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var FilterFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterFactory;

    protected function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->filterFactory = $this->getMock('Sonata\AdminBundle\Filter\FilterFactoryInterface');
        $this->typeGuesser = $this->getMock('Sonata\AdminBundle\Guesser\TypeGuesserInterface');

        $this->datagridBuilder = new DatagridBuilder($this->formFactory, $this->filterFactory, $this->typeGuesser);
    }

    public function testGetBaseDatagrid()
    {
        $admin = $this->getMockBuilder('Sonata\AdminBundle\Admin\AbstractAdmin')
            ->disableOriginalConstructor()->getMock();
        $admin->expects($this->once())->method('getPagerType')->willReturn(Pager::TYPE_SIMPLE);
        $admin->expects($this->once())->method('getClass')->willReturn('Foo\Bar');
        $admin->expects($this->once())->method('createQuery')
            ->willReturn($this->getMock('Sonata\AdminBundle\Datagrid\ProxyQueryInterface'));
        $admin->expects($this->once())->method('getList')
            ->willReturn($this->getMock('Sonata\AdminBundle\Admin\FieldDescriptionCollection'));

        $modelManager = $this->getMock('Sonata\AdminBundle\Model\ModelManagerInterface');
        $modelManager->expects($this->once())->method('getIdentifierFieldNames')->willReturn(array('id'));
        $admin->expects($this->once())->method('getModelManager')->willReturn($modelManager);

        $this->formFactory->expects($this->once())->method('createNamedBuilder')
            ->willReturn($this->getMock('Symfony\Component\Form\FormBuilderInterface'));

        $this->assertInstanceOf('Sonata\AdminBundle\Datagrid\Datagrid', $this->datagridBuilder->getBaseDatagrid($admin));
    }
}
