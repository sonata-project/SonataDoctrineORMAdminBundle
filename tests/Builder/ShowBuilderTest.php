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
use Sonata\AdminBundle\Templating\TemplateRegistry;
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
            [
                'fakeTemplate' => 'fake',
                TemplateRegistry::TYPE_ONE_TO_ONE => '@SonataAdmin/CRUD/Association/show_one_to_one.html.twig',
                TemplateRegistry::TYPE_ONE_TO_MANY => '@SonataAdmin/CRUD/Association/show_one_to_many.html.twig',
                TemplateRegistry::TYPE_MANY_TO_ONE => '@SonataAdmin/CRUD/Association/show_many_to_one.html.twig',
                TemplateRegistry::TYPE_MANY_TO_MANY => '@SonataAdmin/CRUD/Association/show_many_to_many.html.twig',
            ]
        );

        $this->admin = $this->prophesize(AdminInterface::class);
        $this->modelManager = $this->prophesize(ModelManager::class);

        $this->admin->getClass()->willReturn('FakeClass');
        $this->admin->getModelManager()->willReturn($this->modelManager->reveal());
    }

    public function testGetBaseList(): void
    {
        $this->assertInstanceOf(FieldDescriptionCollection::class, $this->showBuilder->getBaseList());
    }

    public function testAddFieldNoType(): void
    {
        $typeGuess = $this->prophesize(TypeGuess::class);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('FakeName');
        $fieldDescription->setMappingType(ClassMetadata::MANY_TO_ONE);

        $this->admin->attachAdminClass(Argument::cetera())->shouldBeCalled();
        $this->admin->addShowFieldDescription(Argument::cetera())->shouldBeCalled();

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

    public function testAddFieldWithType(): void
    {
        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('FakeName');

        $this->admin->addShowFieldDescription(Argument::cetera())->shouldBeCalled();

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
     * @dataProvider fixFieldDescriptionDeprecatedData
     */
    public function testFixFieldDescription(string $type, int $mappingType, string $template): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('FakeName');
        $fieldDescription->setType($type);
        $fieldDescription->setMappingType($mappingType);

        $this->admin->attachAdminClass(Argument::cetera())->shouldBeCalled();

        $this->modelManager->hasMetadata(Argument::any())->willReturn(true);

        $this->modelManager->getParentMetadataForProperty(Argument::cetera())
            ->willReturn([$classMetadata, 2, $parentAssociationMapping = []]);

        $classMetadata->fieldMappings = [2 => []];

        $classMetadata->associationMappings = [2 => ['fieldName' => 'fakeField']];

        $this->showBuilder->fixFieldDescription($this->admin->reveal(), $fieldDescription);

        $this->assertSame($template, $fieldDescription->getTemplate());
    }

    public function fixFieldDescriptionData(): iterable
    {
        return [
            'one-to-one' => [
                TemplateRegistry::TYPE_ONE_TO_ONE,
                ClassMetadata::ONE_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_one_to_one.html.twig',
            ],
            'many-to-one' => [
                TemplateRegistry::TYPE_MANY_TO_ONE,
                ClassMetadata::MANY_TO_ONE,
                '@SonataAdmin/CRUD/Association/show_many_to_one.html.twig',
            ],
            'one-to-many' => [
                TemplateRegistry::TYPE_ONE_TO_MANY,
                ClassMetadata::ONE_TO_MANY,
                '@SonataAdmin/CRUD/Association/show_one_to_many.html.twig',
            ],
            'many-to-many' => [
                TemplateRegistry::TYPE_MANY_TO_MANY,
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
}
