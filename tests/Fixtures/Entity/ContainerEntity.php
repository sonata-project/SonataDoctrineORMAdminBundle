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

use Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable\EmbeddedEntity;

final class ContainerEntity
{
    public ?int $plainField = null;

    public function __construct(
        private AssociatedEntity $associatedEntity,
        public EmbeddedEntity $embeddedEntity
    ) {
    }

    public function getAssociatedEntity(): AssociatedEntity
    {
        return $this->associatedEntity;
    }
}
