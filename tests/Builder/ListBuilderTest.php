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

    protected function setUp(): void
    {
        $this->typeGuesser = $this->createStub(TypeGuesserInterface::class);
        $this->modelManager = $this->createStub(ModelManager::class);
        $this->admin = $this->createMock(AdminInterface::class);

        $this->admin->method('getClass')->willReturn('Foo');
        $this->admin->method('getModelManager')->willReturn($this->modelManager);

        $this->listBuilder = new ListBuilder($this->typeGuesser);
    }

    public function testAddListActionField(): void
    {
        $this->admin->expects($this->once())->method('addListFieldDescription');

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('foo');

        $list = $this->listBuilder->getBaseList();
        $this->listBuilder->addField($list, 'actions', $fieldDescription, $this->admin);

        $this->assertSame(
            '@SonataAdmin/CRUD/list__action.html.twig',
            $list->get('foo')->getTemplate(),
            'Custom list action field has a default list action template assigned'
        );
    }

    public function testCorrectFixedActionsFieldType(): void
    {
        $this->admin->expects($this->once())->method('addListFieldDescription');
        $this->typeGuesser->method('guessType')->willReturn(new TypeGuess('_action', [], Guess::LOW_CONFIDENCE));

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('_action');
        $fieldDescription->setOption('actions', ['test' => []]);

        $list = $this->listBuilder->getBaseList();
        $this->listBuilder->addField($list, null, $fieldDescription, $this->admin);

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
    public function testFixFieldDescription($type, $template): void
    {
        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->fieldMappings = [2 => [1 => 'test', 'type' => 'string']];
        $classMetadata->associationMappings = [2 => ['fieldName' => 'fake']];

        $this->modelManager->method('hasMetadata')->willReturn(true);
        $this->admin->expects($this->once())->method('attachAdminClass');
        $this->modelManager->method('getParentMetadataForProperty')->willReturn([$classMetadata, 2, []]);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('test');
        $fieldDescription->setOption('sortable', true);
        $fieldDescription->setType('fakeType');
        $fieldDescription->setMappingType($type);

        $this->listBuilder->fixFieldDescription($this->admin, $fieldDescription);

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

    public function testFixFieldDescriptionException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->listBuilder->fixFieldDescription($this->admin, new FieldDescription());
    }
}
