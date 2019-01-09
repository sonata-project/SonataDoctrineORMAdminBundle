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

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Builder\ListBuilder;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @author Andrew Mor-Yaroslavtsev <andrejs@gmail.com>
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class ListBuilderTest extends TestCase
{
    private $typeGuesser;
    private $listBuilder;
    private $admin;
    private $modelManager;

    protected function setUp()
    {
        $this->typeGuesser = $this->prophesize(TypeGuesserInterface::class);

        $this->modelManager = $this->prophesize(ModelManager::class);

        $this->admin = $this->prophesize(AdminInterface::class);
        $this->admin->getClass()->willReturn('Foo');
        $this->admin->getModelManager()->willReturn($this->modelManager);
        $this->admin->addListFieldDescription(Argument::cetera())->willReturn();

        $this->listBuilder = new ListBuilder($this->typeGuesser->reveal());
    }

    public function testAddListActionField()
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

    public function testCorrectFixedActionsFieldType()
    {
        $this->typeGuesser->guessType(Argument::cetera())
            ->willReturn(new TypeGuess('_action', [], Guess::LOW_CONFIDENCE));

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('_action');
        $fieldDescription->setOption('actions', ['test' => []]);
        $list = $this->listBuilder->getBaseList();
        $this->listBuilder->addField($list, null, $fieldDescription, $this->admin->reveal());

        $this->assertSame(
            'actions',
            $list->get('_action')->getType(),
            'Standard list _action field has "actions" type'
        );

        $this->assertSame(
            '@SonataAdmin/CRUD/list__action_test.html.twig',
            $fieldDescription->getOption('actions')['test']['template']
        );
    }

    /**
     * @dataProvider fixFieldDescriptionData
     */
    public function testFixFieldDescription($type, $template)
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);
        $this->modelManager->hasMetadata(Argument::any())->willReturn(true);
        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('test');
        $fieldDescription->setOption('sortable', true);
        $fieldDescription->setType('fakeType');
        $fieldDescription->setMappingType($type);

        $this->admin->attachAdminClass(Argument::any())->shouldBeCalledTimes(1);

        $classMetadata->fieldMappings = [2 => [1 => 'test', 'type' => 'string']];
        $this->modelManager->getParentMetadataForProperty(Argument::cetera())
            ->willReturn([$classMetadata, 2, $parentAssociationMapping = []]);

        $classMetadata->associationMappings = [2 => ['fieldName' => 'fake']];

        $this->listBuilder->fixFieldDescription($this->admin->reveal(), $fieldDescription);

        $this->assertSame($template, $fieldDescription->getTemplate());
    }

    public function fixFieldDescriptionData()
    {
        return [
            'one-to-one' => [
                ClassMetadata::ONE_TO_ONE,
                '@SonataAdmin/CRUD/Association/list_one_to_one.html.twig',
            ],
            'many-to-one' => [
                ClassMetadata::MANY_TO_ONE,
                '@SonataAdmin/CRUD/Association/list_many_to_one.html.twig',
            ],
            'one-to-many' => [
                ClassMetadata::ONE_TO_MANY,
                '@SonataAdmin/CRUD/Association/list_one_to_many.html.twig',
            ],
            'many-to-many' => [
                ClassMetadata::MANY_TO_MANY,
                '@SonataAdmin/CRUD/Association/list_many_to_many.html.twig',
            ],
        ];
    }

    public function testFixFieldDescriptionException()
    {
        $this->expectException(\RuntimeException::class);
        $this->listBuilder->fixFieldDescription($this->admin->reveal(), new FieldDescription());
    }
}
