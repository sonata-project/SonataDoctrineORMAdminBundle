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
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Datagrid\Datagrid;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\Pager;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Datagrid\SimplePager;
use Sonata\AdminBundle\Filter\FilterFactoryInterface;
use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Sonata\AdminBundle\Translator\FormLabelTranslatorStrategy;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Builder\DatagridBuilder;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
final class DatagridBuilderTest extends TestCase
{
    /**
     * @var DatagridBuilder
     */
    private $datagridBuilder;
    private $typeGuesser;
    private $formFactory;
    private $filterFactory;
    private $admin;
    private $modelManager;

    protected function setUp(): void
    {
        $this->formFactory = $this->prophesize(FormFactoryInterface::class);
        $this->filterFactory = $this->prophesize(FilterFactoryInterface::class);
        $this->typeGuesser = $this->prophesize(TypeGuesserInterface::class);

        $this->datagridBuilder = new DatagridBuilder(
            $this->formFactory->reveal(),
            $this->filterFactory->reveal(),
            $this->typeGuesser->reveal()
        );

        $this->admin = $this->prophesize(AbstractAdmin::class);
        $this->modelManager = $this->prophesize(ModelManager::class);

        $this->admin->getClass()->willReturn('FakeClass');
        $this->admin->getModelManager()->willReturn($this->modelManager->reveal());
    }

    /**
     * @dataProvider getBaseDatagridData
     */
    public function testGetBaseDatagrid($pagerType, $pager): void
    {
        $proxyQuery = $this->prophesize(ProxyQueryInterface::class);
        $fieldDescription = $this->prophesize(FieldDescriptionCollection::class);
        $formBuilder = $this->prophesize(FormBuilderInterface::class);

        $this->admin->getPagerType()->willReturn($pagerType);
        $this->admin->createQuery()->willReturn($proxyQuery->reveal());
        $this->admin->getList()->willReturn($fieldDescription->reveal());

        $this->modelManager->getIdentifierFieldNames(Argument::any())->willReturn(['id']);

        $this->formFactory->createNamedBuilder(Argument::cetera())->willReturn($formBuilder->reveal());

        $this->assertInstanceOf(
            Datagrid::class,
            $datagrid = $this->datagridBuilder->getBaseDatagrid($this->admin->reveal())
        );
        $this->assertInstanceOf($pager, $datagrid->getPager());
    }

    public function getBaseDatagridData(): array
    {
        return [
            'simple' => [
                Pager::TYPE_SIMPLE,
                SimplePager::class,
            ],
            'default' => [
                Pager::TYPE_DEFAULT,
                Pager::class,
            ],
        ];
    }

    public function testGetBaseDatagridBadPagerType(): void
    {
        $this->admin->getPagerType()->willReturn('fake');

        $this->expectException(\RuntimeException::class);

        $this->datagridBuilder->getBaseDatagrid($this->admin->reveal());
    }

    public function testFixFieldDescription(): void
    {
        $classMetadata = $this->prophesize(ClassMetadata::class);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('test');
        $fieldDescription->setMappingType(ClassMetadata::ONE_TO_MANY);

        $this->admin->attachAdminClass(Argument::cetera())->shouldBeCalled();

        $this->modelManager->hasMetadata(Argument::any())->willReturn(true);

        $this->modelManager->getParentMetadataForProperty(Argument::cetera())
            ->willReturn([$classMetadata, 'someField', $parentAssociationMapping = []])
            ->shouldBeCalledTimes(1);

        $classMetadata->fieldMappings = [
            'someField' => [
                'type' => 'string',
                'declaredField' => 'someFieldDeclared',
                'fieldName' => 'fakeField',
            ],
        ];
        $classMetadata->associationMappings = ['someField' => ['fieldName' => 'fakeField']];
        $classMetadata->embeddedClasses = ['someFieldDeclared' => ['fieldName' => 'fakeField']];

        $this->datagridBuilder->fixFieldDescription($this->admin->reveal(), $fieldDescription);
    }

    public function testAddFilterNoType(): void
    {
        $this->admin->addFilterFieldDescription(Argument::cetera())->shouldBeCalled();

        $datagrid = $this->prophesize(DatagridInterface::class);
        $guessType = $this->prophesize(TypeGuess::class);

        $fieldDescription = new FieldDescription();
        $fieldDescription->setName('test');

        $this->typeGuesser->guessType(Argument::cetera())->willReturn($guessType->reveal());
        $guessType->getOptions()->willReturn(['name' => 'value']);

        $guessType->getType()->willReturn($typeGuessReturn = ModelAutocompleteFilter::class);

        $this->modelManager->hasMetadata(Argument::cetera())->willReturn(false)->shouldBeCalledTimes(1);

        $this->admin->getCode()->willReturn('someFakeCode');

        $this->filterFactory->create(Argument::cetera())->willReturn(new ModelAutocompleteFilter());

        $this->admin->getLabelTranslatorStrategy()->willReturn(new FormLabelTranslatorStrategy());

        $datagrid->addFilter(Argument::type(ModelAutocompleteFilter::class));

        $this->datagridBuilder->addFilter(
            $datagrid->reveal(),
            null,
            $fieldDescription,
            $this->admin->reveal()
        );
    }
}
