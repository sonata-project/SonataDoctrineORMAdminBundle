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
use SimpleThings\EntityAudit\Exception\AuditException;
use Sonata\AdminBundle\Model\AuditReaderInterface;

final class AuditReader implements AuditReaderInterface
{
    /**
     * @var SimpleThingsAuditReader
     */
    private $auditReader;

    public function __construct(SimpleThingsAuditReader $auditReader)
    {
        $this->auditReader = $auditReader;
    }

    public function find(string $className, $id, $revisionId): ?object
    {
        try {
            return $this->auditReader->find($className, $id, $revisionId);
        } catch (AuditException $exception) {
            return null;
        }
    }

    public function findRevisionHistory(string $className, ?int $limit = 20, ?int $offset = 0): array
    {
        return $this->auditReader->findRevisionHistory($limit, $offset);
    }

    public function findRevision(string $className, $revisionId): ?object
    {
        try {
            return $this->auditReader->findRevision($revisionId);
        } catch (AuditException $exception) {
            return null;
        }
    }

    public function findRevisions(string $className, $id): array
    {
        try {
            return $this->auditReader->findRevisions($className, $id);
        } catch (AuditException $exception) {
            return [];
        }
    }

    public function diff(string $className, $id, $oldRevisionId, $newRevisionId): array
    {
        try {
            return $this->auditReader->diff($className, $id, $oldRevisionId, $newRevisionId);
        } catch (AuditException $exception) {
            return [];
        }
    }
}
