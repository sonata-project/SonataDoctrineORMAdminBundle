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

use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use Sonata\AdminBundle\Model\AuditReaderInterface;

class AuditReader implements AuditReaderInterface
{
    /**
     * @var SimpleThingsAuditReader
     */
    protected $auditReader;

    public function __construct(SimpleThingsAuditReader $auditReader)
    {
        $this->auditReader = $auditReader;
    }

    public function find(string $className, $id, $revision): ?object
    {
        return $this->auditReader->find($className, $id, $revision);
    }

    public function findRevisionHistory(string $className, ?int $limit = 20, ?int $offset = 0): array
    {
        return $this->auditReader->findRevisionHistory($limit, $offset);
    }

    public function findRevision(string $classname, $revision): ?object
    {
        return $this->auditReader->findRevision($revision);
    }

    public function findRevisions(string $className, $id): array
    {
        return $this->auditReader->findRevisions($className, $id);
    }

    public function diff(string $className, $id, $oldRevision, $newRevision): array
    {
        return $this->auditReader->diff($className, $id, $oldRevision, $newRevision);
    }
}
