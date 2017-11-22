<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Guesser;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\DoctrineORMAdminBundle\Model\MissingPropertyMetadataException;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class FilterTypeGuesser extends AbstractTypeGuesser
{
    /**
     * {@inheritdoc}
     */
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

        list($metadata, $propertyName, $parentAssociationMappings) = $ret;

        $options['parent_association_mappings'] = $parentAssociationMappings;

        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::MANY_TO_ONE:
                case ClassMetadataInfo::MANY_TO_MANY:
                    $options['operator_type'] = 'Sonata\CoreBundle\Form\Type\EqualType';
                    $options['operator_options'] = [];

                    $options['field_type'] = 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
                    $options['field_options'] = [
                        'class' => $mapping['targetEntity'],
                    ];

                    $options['field_name'] = $mapping['fieldName'];
                    $options['mapping_type'] = $mapping['type'];

                    return new TypeGuess('doctrine_orm_model', $options, Guess::HIGH_CONFIDENCE);
            }
        }

        if (!array_key_exists($propertyName, $metadata->fieldMappings)) {
            throw new MissingPropertyMetadataException($class, $property);
        }

        $options['field_name'] = $metadata->fieldMappings[$propertyName]['fieldName'];

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'boolean':
                $options['field_type'] = 'Sonata\CoreBundle\Form\Type\BooleanType';
                $options['field_options'] = [];

                return new TypeGuess('doctrine_orm_boolean', $options, Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'vardatetime':
            case 'datetimetz':
                return new TypeGuess('doctrine_orm_datetime', $options, Guess::HIGH_CONFIDENCE);
            case 'date':
                return new TypeGuess('doctrine_orm_date', $options, Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
            case 'integer':
            case 'bigint':
            case 'smallint':
                $options['field_type'] = 'Symfony\Component\Form\Extension\Core\Type\NumberType';

                return new TypeGuess('doctrine_orm_number', $options, Guess::MEDIUM_CONFIDENCE);
            case 'string':
            case 'text':
                $options['field_type'] = 'Symfony\Component\Form\Extension\Core\Type\TextType';

                return new TypeGuess('doctrine_orm_string', $options, Guess::MEDIUM_CONFIDENCE);
            case 'time':
                return new TypeGuess('doctrine_orm_time', $options, Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess('doctrine_orm_string', $options, Guess::LOW_CONFIDENCE);
        }
    }
}
