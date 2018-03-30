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

use Doctrine\ORM\Mapping\ClassMetadata;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class TypeGuesser extends AbstractTypeGuesser
{
    public function guessType($class, $property, ModelManagerInterface $modelManager)
    {
        if (!$ret = $this->getParentMetadataForProperty($class, $property, $modelManager)) {
            return new TypeGuess('text', [], Guess::LOW_CONFIDENCE);
        }

        list($metadata, $propertyName, $parentAssociationMappings) = $ret;

        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);

            switch ($mapping['type']) {
                case ClassMetadata::ONE_TO_MANY:
                    return new TypeGuess('orm_one_to_many', [], Guess::HIGH_CONFIDENCE);

                case ClassMetadata::MANY_TO_MANY:
                    return new TypeGuess('orm_many_to_many', [], Guess::HIGH_CONFIDENCE);

                case ClassMetadata::MANY_TO_ONE:
                    return new TypeGuess('orm_many_to_one', [], Guess::HIGH_CONFIDENCE);

                case ClassMetadata::ONE_TO_ONE:
                    return new TypeGuess('orm_one_to_one', [], Guess::HIGH_CONFIDENCE);
            }
        }

        switch ($metadata->getTypeOfField($propertyName)) {
            case 'array':
            case 'json':
            case 'json_array':
                return new TypeGuess('array', [], Guess::HIGH_CONFIDENCE);
            case 'boolean':
                return new TypeGuess('boolean', [], Guess::HIGH_CONFIDENCE);
            case 'datetime':
            case 'datetime_immutable':
            case 'vardatetime':
            case 'datetimetz':
            case 'datetimetz_immutable':
                return new TypeGuess('datetime', [], Guess::HIGH_CONFIDENCE);
            case 'date':
            case 'date_immutable':
                return new TypeGuess('date', [], Guess::HIGH_CONFIDENCE);
            case 'decimal':
            case 'float':
                return new TypeGuess('number', [], Guess::MEDIUM_CONFIDENCE);
            case 'integer':
            case 'bigint':
            case 'smallint':
                return new TypeGuess('integer', [], Guess::MEDIUM_CONFIDENCE);
            case 'string':
                return new TypeGuess('text', [], Guess::MEDIUM_CONFIDENCE);
            case 'text':
                return new TypeGuess('textarea', [], Guess::MEDIUM_CONFIDENCE);
            case 'time':
            case 'time_immutable':
                return new TypeGuess('time', [], Guess::HIGH_CONFIDENCE);
            default:
                return new TypeGuess('text', [], Guess::LOW_CONFIDENCE);
        }
    }
}
