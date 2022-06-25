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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

final class Product
{
    private ProductId $id;

    private string $name;

    public function __construct(ProductId $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
