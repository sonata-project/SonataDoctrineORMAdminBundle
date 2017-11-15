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

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Datagrid\Pager;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DatagridBuilderTest extends TestCase
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
        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $this->filterFactory = $this->createMock('Sonata\AdminBundle\Filter\FilterFactoryInterface');
        $this->typeGuesser = $this->createMock('Sonata\AdminBundle\Guesser\TypeGuesserInterface');

        $this->datagridBuilder = new DatagridBuilder($this->formFactory, $this->filterFactory, $this->typeGuesser);
    }

    public function testGetBaseDatagrid()
    {
        $admin = $this->createMock('Sonata\AdminBundle\Admin\AbstractAdmin');
        $admin->expects($this->once())->method('getPagerType')->willReturn(Pager::TYPE_SIMPLE);
        $admin->expects($this->once())->method('getClass')->willReturn('Foo\Bar');
        $admin->expects($this->once())->method('createQuery')
            ->willReturn($this->createMock('Sonata\AdminBundle\Datagrid\ProxyQueryInterface'));
        $admin->expects($this->once())->method('getList')
            ->willReturn($this->createMock('Sonata\AdminBundle\Admin\FieldDescriptionCollection'));

        $modelManager = $this->createMock('Sonata\AdminBundle\Model\ModelManagerInterface');
        $modelManager->expects($this->once())->method('getIdentifierFieldNames')->willReturn(['id']);
        $admin->expects($this->once())->method('getModelManager')->willReturn($modelManager);

        $this->formFactory->expects($this->once())->method('createNamedBuilder')
            ->willReturn($this->createMock('Symfony\Component\Form\FormBuilderInterface'));

        $this->assertInstanceOf('Sonata\AdminBundle\Datagrid\Datagrid', $this->datagridBuilder->getBaseDatagrid($admin));
    }
}
