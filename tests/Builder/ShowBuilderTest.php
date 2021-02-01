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

        $this->showBuilder->addField(
            new FieldDescriptionCollection(),
            'someType',
            $fieldDescription,
            $this->admin
        );
    }

    /**
     * @dataProvider fixFieldDescriptionData
     */
    public function testFixFieldDescription(string $type, int $mappingType, string $template): void
    {
        $fieldDescription = new FieldDescription('FakeName', [], ['type' => $mappingType]);
        $fieldDescription->setType($type);

        $this->admin->expects($this->once())->method('attachAdminClass');

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

    public function testFixFieldDescriptionException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->showBuilder->fixFieldDescription($this->admin, new FieldDescription('name'));
    }
}
