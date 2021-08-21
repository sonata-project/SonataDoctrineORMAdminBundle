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
use SimpleThings\EntityAudit\Revision as EntityAuditRevision;
use Sonata\AdminBundle\Model\AuditReaderInterface;
use Sonata\AdminBundle\Model\Revision;

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
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function findRevisionHistory(string $className, ?int $limit = 20, ?int $offset = 0): array
    {
        return array_map(
            [$this, 'createRevisionFromEntityAuditRevision'],
            $this->auditReader->findRevisionHistory($limit, $offset)
        );
    }

    public function findRevision(string $className, $revisionId): ?Revision
    {
        try {
            return $this->createRevisionFromEntityAuditRevision($this->auditReader->findRevision($revisionId));
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function findRevisions(string $className, $id): array
    {
        try {
            return array_map(
                [$this, 'createRevisionFromEntityAuditRevision'],
                $this->auditReader->findRevisions($className, $id)
            );
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function diff(string $className, $id, $oldRevisionId, $newRevisionId): array
    {
        try {
            return $this->auditReader->diff($className, $id, $oldRevisionId, $newRevisionId);
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private function createRevisionFromEntityAuditRevision(EntityAuditRevision $revision): Revision
    {
        return new Revision($revision->getRev(), $revision->getTimestamp(), $revision->getUsername());
    }
}
