<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Guesser;

use Sonata\AdminBundle\Guesser\TypeGuesserInterface;
use Doctrine\ORM\Mapping\MappingException;
use Sonata\AdminBundle\Model\ModelManagerInterface;

abstract class AbstractTypeGuesser implements TypeGuesserInterface
{
    /**
     * @param string $class
     * @param string $property
     * @return TypeGuess
     */
    abstract public function guessType($class, $property, ModelManagerInterface $modelManager);

    protected function getParentMetadataForProperty($baseClass, $propertyFullName, $modelManager)
    {
        try {
            return $modelManager->getParentMetadataForProperty($baseClass, $propertyFullName);
        } catch (MappingException $e) {
            // no metadata not found.
            return null;
        }
    }
}
