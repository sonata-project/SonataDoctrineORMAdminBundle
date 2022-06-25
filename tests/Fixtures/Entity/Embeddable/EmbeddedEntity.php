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

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity\Embeddable;

final class EmbeddedEntity
{
    /**
     * @var bool|null
     */
    public $plainField;

    private ?SubEmbeddedEntity $subEmbeddedEntity = null;

    public function setPlainField(bool $plainField): void
    {
        $this->plainField = $plainField;
    }

    public function getPlaingField(): ?bool
    {
        return $this->plainField;
    }

    public function setSubEmbeddedEntity(SubEmbeddedEntity $subEmbeddedEntity): void
    {
        $this->subEmbeddedEntity = $subEmbeddedEntity;
    }

    public function getSubEmbeddedEntity(): ?SubEmbeddedEntity
    {
        return $this->subEmbeddedEntity;
    }
}
