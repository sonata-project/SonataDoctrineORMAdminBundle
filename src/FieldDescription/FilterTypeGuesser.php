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

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\FieldDescription\TypeGuesserInterface;
use Sonata\AdminBundle\Form\Type\Operator\EqualOperatorType;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\NumberFilter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\DoctrineORMAdminBundle\Filter\TimeFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

final class FilterTypeGuesser implements TypeGuesserInterface
{
    public function guess(FieldDescriptionInterface $fieldDescription): TypeGuess
    {
        $options = [
            'field_name' => $fieldDescription->getFieldName(),
            'parent_association_mappings' => $fieldDescription->getParentAssociationMappings(),
        ];

        switch ($fieldDescription->getMappingType()) {
            case 'boolean':
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
                return new TypeGuess(NumberFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                $options['field_type'] = IntegerType::class;

                return new TypeGuess(NumberFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                return new TypeGuess(StringFilter::class, $options, Guess::MEDIUM_CONFIDENCE);
            case 'time':
            case 'time_immutable':
                return new TypeGuess(TimeFilter::class, $options, Guess::HIGH_CONFIDENCE);
            case ClassMetadata::ONE_TO_ONE:
            case ClassMetadata::ONE_TO_MANY:
            case ClassMetadata::MANY_TO_ONE:
            case ClassMetadata::MANY_TO_MANY:
                $options['operator_type'] = EqualOperatorType::class;
                $options['operator_options'] = [];
                $options['field_type'] = EntityType::class;
                $options['field_options'] = ['class' => $fieldDescription->getTargetModel()];
                $options['mapping_type'] = $fieldDescription->getMappingType();

                return new TypeGuess(ModelFilter::class, $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess(StringFilter::class, $options, Guess::LOW_CONFIDENCE);
        }
    }
}
