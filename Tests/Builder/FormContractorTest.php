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

use Sonata\DoctrineORMAdminBundle\Builder\FormContractor;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class FormContractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var FormContractor
     */
    private $formContractor;

    protected function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->formContractor = new FormContractor($this->formFactory);
    }

    public function testGetFormBuilder()
    {
        $this->formFactory->expects($this->once())->method('createNamedBuilder')
            ->willReturn($this->getMock('Symfony\Component\Form\FormBuilderInterface'));

        $this->assertInstanceOf(
            'Symfony\Component\Form\FormBuilderInterface',
            $this->formContractor->getFormBuilder('test', array('foo' => 'bar'))
        );
    }
}
