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
        $this->formFactory = $this->createStub(FormFactoryInterface::class);
        $this->filterFactory = $this->createStub(FilterFactoryInterface::class);
        $this->typeGuesser = $this->createStub(TypeGuesserInterface::class);

        $this->datagridBuilder = new DatagridBuilder(
            $this->formFactory,
            $this->filterFactory,
            $this->typeGuesser
        );

        $this->admin = $this->createMock(AdminInterface::class);
        $this->modelManager = $this->createMock(ModelManager::class);

        $this->admin->method('getClass')->willReturn('FakeClass');
        $this->admin->method('getModelManager')->willReturn($this->modelManager);
    }

    /**
     * @dataProvider getBaseDatagridData
     */
    public function testGetBaseDatagrid($pagerType, $pager): void
    {
        $proxyQuery = $this->createStub(ProxyQueryInterface::class);
        $fieldDescription = new FieldDescriptionCollection();
        $formBuilder = $this->createStub(FormBuilderInterface::class);

        $this->admin->method('getPagerType')->willReturn($pagerType);
        $this->admin->method('createQuery')->willReturn($proxyQuery);
        $this->admin->method('getList')->willReturn($fieldDescription);
        $this->modelManager->method('getIdentifierFieldNames')->willReturn(['id']);
        $this->formFactory->method('createNamedBuilder')->willReturn($formBuilder);

        $datagrid = $this->datagridBuilder->getBaseDatagrid($this->admin);

        $this->assertInstanceOf(Datagrid::class, $datagrid);
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
        $this->admin->method('getPagerType')->willReturn('fake');

        $this->expectException(\RuntimeException::class);

        $this->datagridBuilder->getBaseDatagrid($this->admin);
    }

    public function testFixFieldDescription(): void
    {
        $fieldDescription = new FieldDescription('test', [], ['type' => ClassMetadata::ONE_TO_MANY]);

        $this->admin->expects($this->once())->method('attachAdminClass');

        $this->datagridBuilder->fixFieldDescription($this->admin, $fieldDescription);
    }

    public function testAddFilterNoType(): void
    {
        $datagrid = $this->createStub(DatagridInterface::class);
        $guessType = $this->createStub(TypeGuess::class);

        $fieldDescription = new FieldDescription('test');

        $this->admin->expects($this->once())->method('addFilterFieldDescription');
        $this->admin->method('getCode')->willReturn('someFakeCode');
        $this->admin->method('getLabelTranslatorStrategy')->willReturn(new FormLabelTranslatorStrategy());
        $this->typeGuesser->method('guessType')->willReturn($guessType);
        $this->filterFactory->method('create')->willReturn(new ModelAutocompleteFilter());

        $guessType->method('getOptions')->willReturn(['name' => 'value']);
        $guessType->method('getType')->willReturn(ModelAutocompleteFilter::class);
        $datagrid->method('addFilter')->with($this->isInstanceOf(ModelAutocompleteFilter::class));

        $this->datagridBuilder->addFilter($datagrid, null, $fieldDescription, $this->admin);
    }
}
