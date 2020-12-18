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

namespace Sonata\DoctrineORMAdminBundle\Filter;

/**
 * @psalm-suppress InvalidExtendClass
 */
final class EmptyFilter extends NullFilter
{
    public function __construct()
    {
        // NEXT_MAJOR: remove this file
        @trigger_error(sprintf(
            'The %s class is deprecated since version 3.x and will be removed in 4.0.'
            .' Use %s instead.',
            __CLASS__,
            NullFilter::class
        ), E_USER_DEPRECATED);
    }
}
