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

namespace Sonata\DoctrineORMAdminBundle\Tests\Block;

use Prophecy\Argument;
use SimpleThings\EntityAudit\AuditReader as SimpleThingsAuditReader;
use SimpleThings\EntityAudit\Revision;
use Sonata\BlockBundle\Block\BlockContext;
use Sonata\BlockBundle\Model\Block;
use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase;
use Sonata\DoctrineORMAdminBundle\Block\AuditBlockService;

/**
 * @author Marko Kunic <kunicmarko20@gmail.com>
 */
class AuditBlockServiceTest extends AbstractBlockServiceTestCase
{
    private $simpleThingsAuditReader;
    private $blockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleThingsAuditReader = $this->prophesize(SimpleThingsAuditReader::class);

        $this->blockService = new AuditBlockService(
            'block.service',
            $this->templating,
            $this->simpleThingsAuditReader->reveal()
        );
    }

    /**
     * @group legacy
     */
    public function testExecute(): void
    {
        $blockContext = $this->prophesize(BlockContext::class);

        $blockContext->getBlock()->willReturn($block = new Block())->shouldBeCalledTimes(1);
        $blockContext->getSetting('limit')->willReturn($limit = 10)->shouldBeCalledTimes(1);

        $this->simpleThingsAuditReader->findRevisionHistory($limit, 0)
            ->willReturn([$revision = new Revision('test', '123', 'test')])
            ->shouldBeCalledTimes(1);

        $this->simpleThingsAuditReader->findEntitesChangedAtRevision(Argument::cetera())
            ->willReturn([])
            ->shouldBeCalledTimes(1);

        $blockContext->getTemplate()->willReturn('template')->shouldBeCalledTimes(1);
        $blockContext->getSettings()->willReturn([])->shouldBeCalledTimes(1);

        $this->blockService->execute($blockContext->reveal());

        $this->assertSame('template', $this->templating->view);
        $this->assertInternalType('array', $this->templating->parameters['settings']);
        $this->assertSame($revision, $this->templating->parameters['revisions'][0]['revision']);
        $this->assertSame($block, $this->templating->parameters['block']);
    }

    public function testDefaultSettings(): void
    {
        $blockContext = $this->getBlockContext($this->blockService);

        $this->assertSettings([
            'attr' => [],
            'extra_cache_keys' => [],
            'limit' => 10,
            'template' => '@SonataDoctrineORMAdmin/Block/block_audit.html.twig',
            'ttl' => 0,
            'use_cache' => true,
        ], $blockContext);
    }
}
