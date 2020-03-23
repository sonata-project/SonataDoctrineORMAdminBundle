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

namespace Sonata\DoctrineORMAdminBundle\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;
use Sonata\DoctrineORMAdminBundle\Model\MissingPropertyMetadataException;
use Sonata\Form\Type\BooleanType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class FilterTypeGuesser extends AbstractTypeGuesser
{
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property, $modelManager)) {
            return false;
        }

        $options = [
            'field_type' => null,
            'field_options' => [],
            'options' => [],
        ];

        [$metadata, $propertyName, $parentAssociationMappings] = $ret;

        $options['parent_association_mappings'] = $parentAssociationMappings;

        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadata::ONE_TO_ONE:
                case ClassMetadata::ONE_TO_MANY:
                case ClassMetadata::MANY_TO_ONE:
                case ClassMetadata::MANY_TO_MANY:
                    $options['operator_type'] = EqualOperatorType::class;
                    $options['operator_options'] = [];
                    $options['field_type'] = EntityType::class;
                    $options['field_options'] = [
                        'class' => $mapping['targetEntity'],
                    ];

                    $options['field_name'] = $mapping['fieldName'];
                    $options['mapping_type'] = $mapping['type'];

                    return new TypeGuess(ModelFilter::class, $options, Guess::HIGH_CONFIDENCE);
            }
        }

        if (!\array_key_exists($propertyName, $metadata->fieldMappings)) {
            throw new MissingPropertyMetadataException($class, $property);
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'boolean':
                $options['field_type'] = BooleanType::class;

                return new TypeGuess(BooleanFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'datetime_immutable':
            case 'vardatetime':
            case 'datetimetz':
            case 'datetimetz_immutable':
                return new TypeGuess(DateTimeFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'date':
            case 'date_immutable':
                return new TypeGuess(DateFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
                $options['field_type'] = NumberType::class;

                return new TypeGuess(NumberFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                $options['field_type'] = IntegerType::class;

                return new TypeGuess(NumberFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                $options['field_type'] = TextType::class;

                return new TypeGuess(StringFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'time':
            case 'time_immutable':
                return new TypeGuess(TimeFilter::class, $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess(StringFilter::class, $options, Guess::LOW_CONFIDENCE);
        }
    }
}
