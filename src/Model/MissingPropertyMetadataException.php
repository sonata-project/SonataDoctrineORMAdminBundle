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

namespace Sonata\DoctrineORMAdminBundle\Model;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class MissingPropertyMetadataException extends \LogicException
{
    /**
     * @phpstan-param class-string $class
     */
    public function __construct(string $class, string $property)
    {
        parent::__construct(sprintf(
            'No metadata found for property `%s::$%s`.'
            .' Please make sure your Doctrine mapping is properly configured.',
            $class,
            $property
        ));
    }
}
