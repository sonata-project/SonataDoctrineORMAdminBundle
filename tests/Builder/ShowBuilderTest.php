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
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
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
        $this->guesser = $this->prophesize(TypeGuesserInterface::class);

        $this->showBuilder = new ShowBuilder(
            $this->guesser->reveal(),
            ['fakeTemplate' => 'fake']
        );

        $this->admin = $this->prophesize(AdminInterface::class);
        $this->modelManager = $this->prophesize(ModelManager::class);

        $this->admin->getClass()->willReturn('FakeClass');
        $this->admin->getModelManager()->willReturn($this->modelManager->reveal());
        $this->admin->attachAdminClass(Argument::cetera())->willReturn();
        $this->admin->addShowFieldDescription(Argument::cetera())->willReturn();
    }

    public function testGetBaseList(): void
    {
        $this->assertInstanceOf(FieldDescriptionCollection::class, $this->showBuilder->getBaseList());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddFieldNoType(): void
    {
        $typeGuess = $this->prophesize(TypeGuess::class);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('FakeName');
        $fieldDescription->setMappingType(ClassMetadata::MANY_TO_ONE);

        $typeGuess->getType()->willReturn($typeGuessReturn = 'fakeType');

        $this->guesser->guessType(Argument::any(), Argument::any(), $this->modelManager)->willReturn($typeGuess);

        $this->modelManager->hasMetadata(Argument::any())->willReturn(false);

        $this->showBuilder->addField(
            new FieldDescriptionCollection(),
            null,
            $fieldDescription,
            $this->admin->reveal()
        );
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddFieldWithType(): void
    {
        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('FakeName');

        $this->modelManager->hasMetadata(Argument::any())->willReturn(false);

        $this->showBuilder->addField(
            new FieldDescriptionCollection(),
            'someType',
            $fieldDescription,
            $this->admin->reveal()
        );
    }

    /**
     * @dataProvider fixFieldDescriptionData
     */
    public function testFixFieldDescription($mappingType, $template): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('FakeName');
        $fieldDescription->setType('someType');
        $fieldDescription->setMappingType($mappingType);

        $this->modelManager->hasMetadata(Argument::any())->willReturn(true);

        $this->modelManager->getParentMetadataForProperty(Argument::cetera())
            ->willReturn([$classMetadata, 2, $parentAssociationMapping = []]);

        $classMetadata->fieldMappings = [2 => []];

        $classMetadata->associationMappings = [2 => ['fieldName' => 'fakeField']];

        $this->showBuilder->fixFieldDescription($this->admin->reveal(), $fieldDescription);

        $this->assertSame($template, $fieldDescription->getTemplate());
    }

    public function fixFieldDescriptionData(): array
    {
        return [
            'one-to-one' => [
                ClassMetadata::ONE_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_one_to_one.html.twig',
            ],
            'many-to-one' => [
                ClassMetadata::MANY_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_many_to_one.html.twig',
            ],
            'one-to-many' => [
                ClassMetadata::ONE_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_one_to_many.html.twig',
            ],
            'many-to-many' => [
                ClassMetadata::MANY_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_many_to_many.html.twig',
            ],
        ];
    }

    public function testFixFieldDescriptionException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->showBuilder->fixFieldDescription($this->admin->reveal(), new FieldDescription());
    }
}
