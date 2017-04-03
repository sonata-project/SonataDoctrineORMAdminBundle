<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Model;

class MissingPropertyMetadataException extends \LogicException
{
    public function __construct($class, $property)
    {
        parent::__construct(sprintf(
            'No metadata found for property `%s::$%s`.'
            .' Please make sure your Doctrine mapping is properly configured.',
            $class,
            $property
        ));
    }
}
