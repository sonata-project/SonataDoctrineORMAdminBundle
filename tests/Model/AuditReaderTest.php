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

namespace Sonata\DoctrineORMAdminBundle\Tests\Model;

use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use Sonata\DoctrineORMAdminBundle\Model\AuditReader;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class AuditReaderTest extends TestCase
{
    private $simpleThingsAuditReader;
    private $auditReader;

    protected function setUp(): void
    {
        $this->simpleThingsAuditReader = $this->prophesize(SimpleThingsAuditReader::class);
        $this->auditReader = new AuditReader($this->simpleThingsAuditReader->reveal());
    }

    public function testFind(): void
    {
        $this->simpleThingsAuditReader
            ->find($className = 'fakeClass', $id = 1, $revision = 2)
            ->shouldBeCalledTimes(1);

        $this->auditReader->find($className, $id, $revision);
    }

    public function testFindRevisionHistory(): void
    {
        $this->simpleThingsAuditReader
            ->findRevisionHistory($limit = 20, $offset = 0)
            ->shouldBeCalledTimes(1)
            ->willReturn([]);

        $this->auditReader->findRevisionHistory('class', $limit, $offset);
    }

    public function testFindRevision(): void
    {
        $this->simpleThingsAuditReader
            ->findRevision($revision = 2)
            ->shouldBeCalledTimes(1);

        $this->auditReader->findRevision('class', $revision);
    }

    public function testFindRevisions(): void
    {
        $this->simpleThingsAuditReader
            ->findRevisions($className = 'fakeClass', $id = 2)
            ->shouldBeCalledTimes(1)
            ->willReturn([]);

        $this->auditReader->findRevisions($className, $id);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDiff(): void
    {
        $this->simpleThingsAuditReader
            ->diff($className = 'fakeClass', $id = 1, $oldRevision = 1, $newRevision = 2)
            ->willReturn([]);

        $this->auditReader->diff($className, $id, $oldRevision, $newRevision);
    }
}
