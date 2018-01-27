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

namespace Sonata\DoctrineORMAdminBundle\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @author Andrew Mor-Yaroslavtsev <andrejs@gmail.com>
 */
class ListBuilderTest extends TestCase
{
    /**
     * @var TypeGuesserInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $typeGuesser;

    /**
     * @var ListBuilder
     */
    protected $listBuilder;

    /**
     * @var AdminInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $admin;

    /**
     * @var ModelManager|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $modelManager;

    protected function setUp(): void
    {
        $this->typeGuesser = $this->prophesize(TypeGuesserInterface::class);

        $this->modelManager = $this->prophesize(ModelManager::class);
        $this->modelManager->hasMetadata(Argument::any())->willReturn(false);

        $this->admin = $this->prophesize(AbstractAdmin::class);
        $this->admin->getClass()->willReturn('Foo');
        $this->admin->getModelManager()->willReturn($this->modelManager);
        $this->admin->addListFieldDescription(Argument::any(), Argument::any())
            ->willReturn();

        $this->listBuilder = new ListBuilder($this->typeGuesser->reveal());
    }

    public function testAddListActionField(): void
    {
        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('foo');
        $list = $this->listBuilder->getBaseList();
        $this->listBuilder
            ->addField($list, 'actions', $fieldDescription, $this->admin->reveal());

        $this->assertSame(
            '@SonataAdmin/CRUD/list__action.html.twig',
            $list->get('foo')->getTemplate(),
            'Custom list action field has a default list action template assigned'
        );
    }

    public function testCorrectFixedActionsFieldType(): void
    {
        $this->typeGuesser->guessType(
            Argument::any(), Argument::any(), Argument::any()
        )->willReturn(
            new TypeGuess('_action', [], Guess::LOW_CONFIDENCE)
        );

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('_action');
        $list = $this->listBuilder->getBaseList();
        $this->listBuilder->addField($list, null, $fieldDescription, $this->admin->reveal());

        $this->assertSame(
            'actions',
            $list->get('_action')->getType(),
            'Standard list _action field has "actions" type'
        );
    }
}
