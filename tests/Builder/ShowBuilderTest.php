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
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Builder\ShowBuilder;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class ShowBuilderTest extends TestCase
{
    private $guesser;
    private $showBuilder;
    private $admin;
    private $modelManager;

    protected function setUp(): void
    {
        $this->guesser = $this->createStub(TypeGuesserInterface::class);

        $this->showBuilder = new ShowBuilder(
            $this->guesser,
            [
                'fakeTemplate' => 'fake',
                FieldDescriptionInterface::TYPE_ONE_TO_ONE => '@SonataAdmin/CRUD/Association/show_one_to_one.html.twig',
                FieldDescriptionInterface::TYPE_ONE_TO_MANY => '@SonataAdmin/CRUD/Association/show_one_to_many.html.twig',
                FieldDescriptionInterface::TYPE_MANY_TO_ONE => '@SonataAdmin/CRUD/Association/show_many_to_one.html.twig',
                FieldDescriptionInterface::TYPE_MANY_TO_MANY => '@SonataAdmin/CRUD/Association/show_many_to_many.html.twig',
            ]
        );

        $this->admin = $this->createMock(AdminInterface::class);
        $this->modelManager = $this->createStub(ModelManager::class);

        $this->admin->method('getClass')->willReturn('FakeClass');
        $this->admin->method('getModelManager')->willReturn($this->modelManager);
    }

    public function testGetBaseList(): void
    {
        $this->assertInstanceOf(FieldDescriptionCollection::class, $this->showBuilder->getBaseList());
    }

    public function testAddFieldNoType(): void
    {
        $typeGuess = $this->createStub(TypeGuess::class);

        $fieldDescription = new FieldDescription('FakeName', [], ['type' => ClassMetadata::MANY_TO_ONE]);

        $this->admin->expects($this->once())->method('attachAdminClass');
        $this->admin->expects($this->once())->method('addShowFieldDescription');

        $typeGuess->method('getType')->willReturn('fakeType');

        $this->guesser->method('guessType')->with($this->anything(), $this->anything(), $this->modelManager)->willReturn($typeGuess);
        $this->modelManager->method('hasMetadata')->willReturn(false);

        $this->showBuilder->addField(
            new FieldDescriptionCollection(),
            null,
            $fieldDescription,
            $this->admin
        );
    }

    public function testAddFieldWithType(): void
    {
        $fieldDescription = new FieldDescription('FakeName');

        $this->admin->expects($this->once())->method('addShowFieldDescription');
        $this->modelManager->method('hasMetadata')->willReturn(false);

        $this->showBuilder->addField(
            new FieldDescriptionCollection(),
            'someType',
            $fieldDescription,
            $this->admin
        );
    }

    /**
     * @dataProvider fixFieldDescriptionData
     * @dataProvider fixFieldDescriptionDeprecatedData
     */
    public function testFixFieldDescription(string $type, int $mappingType, string $template): void
    {
        // NEXT_MAJOR: Remove the next 3 lines.
        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->fieldMappings = [2 => []];
        $classMetadata->associationMappings = [2 => ['fieldName' => 'fakeField']];

        $fieldDescription = new FieldDescription('FakeName', [], ['type' => $mappingType]);
        $fieldDescription->setType($type);

        $this->admin->expects($this->once())->method('attachAdminClass');
        // NEXT_MAJOR: Remove the next 2 lines.
        $this->modelManager->method('hasMetadata')->willReturn(true);
        $this->modelManager->method('getParentMetadataForProperty')->willReturn([$classMetadata, 2, []]);

        $this->showBuilder->fixFieldDescription($this->admin, $fieldDescription);

        $this->assertSame($template, $fieldDescription->getTemplate());
    }

    public function fixFieldDescriptionData(): iterable
    {
        return [
            'one-to-one' => [
                FieldDescriptionInterface::TYPE_ONE_TO_ONE,
                ClassMetadata::ONE_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_one_to_one.html.twig',
            ],
            'many-to-one' => [
                FieldDescriptionInterface::TYPE_MANY_TO_ONE,
                ClassMetadata::MANY_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_many_to_one.html.twig',
            ],
            'one-to-many' => [
                FieldDescriptionInterface::TYPE_ONE_TO_MANY,
                ClassMetadata::ONE_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_one_to_many.html.twig',
            ],
            'many-to-many' => [
                FieldDescriptionInterface::TYPE_MANY_TO_MANY,
                ClassMetadata::MANY_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_many_to_many.html.twig',
            ],
        ];
    }

    /**
     * NEXT_MAJOR: Remove this dataprovider.
     */
    public function fixFieldDescriptionDeprecatedData(): iterable
    {
        return [
            'deprecated-one-to-one' => [
                'orm_one_to_one',
                ClassMetadata::ONE_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_one_to_one.html.twig',
            ],
            'deprecated-many-to-one' => [
                'orm_many_to_one',
                ClassMetadata::MANY_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_many_to_one.html.twig',
            ],
            'deprecated-one-to-many' => [
                'orm_one_to_many',
                ClassMetadata::ONE_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_one_to_many.html.twig',
            ],
            'deprecated-many-to-many' => [
                'orm_many_to_many',
                ClassMetadata::MANY_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_many_to_many.html.twig',
            ],
        ];
    }

    public function testFixFieldDescriptionException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->showBuilder->fixFieldDescription($this->admin, new FieldDescription('name'));
    }
}
