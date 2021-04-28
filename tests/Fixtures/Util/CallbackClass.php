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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Util;

/**
 * NEXT_MAJOR: Remove this class.
 */
final class CallbackClass
{
    public function __invoke($query, $alias, $field, array $data): bool
    {
        return \is_array($data);
    }

    public static function staticCallback($query, $alias, $field, $data): bool
    {
        return \is_array($data);
    }

    public static function callback($query, $alias, $field, $data): bool
    {
        return \is_array($data);
    }
}
