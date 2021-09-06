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

namespace Sonata\DoctrineORMAdminBundle\FieldDescription;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionFactoryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

final class FieldDescriptionFactory implements FieldDescriptionFactoryInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function create(string $class, string $name, array $options = []): FieldDescriptionInterface
    {
        [$metadata, $propertyName, $parentAssociationMappings] = $this->getParentMetadataForProperty($class, $name);

        return new FieldDescription(
            $name,
            $options,
            $metadata->fieldMappings[$propertyName] ?? [],
            $metadata->associationMappings[$propertyName] ?? [],
            $parentAssociationMappings,
            $propertyName
        );
    }

    /**
     * @phpstan-param class-string $baseClass
     *
     * @phpstan-return array{ClassMetadata<object>, string, mixed[]}
     */
    private function getParentMetadataForProperty(string $baseClass, string $propertyFullName): array
    {
        $nameElements = explode('.', $propertyFullName);
        $lastPropertyName = array_pop($nameElements);
        $class = $baseClass;
        $parentAssociationMappings = [];

        foreach ($nameElements as $nameElement) {
            $metadata = $this->getMetadata($class);
            if (!isset($metadata->associationMappings[$nameElement])) {
                break;
            }

            $parentAssociationMappings[] = $metadata->associationMappings[$nameElement];
            $class = $metadata->getAssociationTargetClass($nameElement);
        }

        $properties = \array_slice($nameElements, \count($parentAssociationMappings));
        $properties[] = $lastPropertyName;

        return [
            $this->getMetadata($class),
            implode('.', $properties),
            $parentAssociationMappings,
        ];
    }

    /**
     * @phpstan-template TObject of object
     * @phpstan-param class-string<TObject> $class
     * @phpstan-return ClassMetadata<TObject>
     */
    private function getMetadata(string $class): ClassMetadata
    {
        return $this->getEntityManager($class)->getClassMetadata($class);
    }

    /**
     * @param class-string $class
     *
     * @throw \UnexpectedValueException
     */
    private function getEntityManager(string $class): EntityManagerInterface
    {
        $em = $this->registry->getManagerForClass($class);

        if (!$em instanceof EntityManagerInterface) {
            throw new \UnexpectedValueException(sprintf('No entity manager defined for class "%s".', $class));
        }

        return $em;
    }
}
