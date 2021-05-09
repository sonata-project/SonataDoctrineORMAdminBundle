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

class ContainerEntity
{
    /**
     * @var int|null
     */
    protected $plainField;

    /**
     * @var AssociatedEntity
     */
    protected $associatedEntity;

    /**
     * @var Embeddable\EmbeddedEntity
     */
    protected $embeddedEntity;

    public function __construct(AssociatedEntity $associatedEntity, Embeddable\EmbeddedEntity $embeddedEntity)
    {
        $this->associatedEntity = $associatedEntity;
        $this->embeddedEntity = $embeddedEntity;
    }

    /**
     * @return AssociatedEntity
     */
    public function getAssociatedEntity()
    {
        return $this->associatedEntity;
    }
}
