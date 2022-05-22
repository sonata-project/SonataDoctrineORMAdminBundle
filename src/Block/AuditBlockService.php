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

namespace Sonata\DoctrineORMAdminBundle\Block;

use SimpleThings\EntityAudit\AuditReader;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class AuditBlockService extends AbstractBlockService
{
    /**
     * @var AuditReader
     */
    private $auditReader;

    public function __construct(Environment $twig, AuditReader $auditReader)
    {
        parent::__construct($twig);

        $this->auditReader = $auditReader;
    }

    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        $template = $blockContext->getTemplate();
        \assert(null !== $template);
        $limit = $blockContext->getSetting('limit');
        \assert(\is_int($limit));

        $revisions = [];

        foreach ($this->auditReader->findRevisionHistory($limit, 0) as $revision) {
            $revisions[] = [
                'revision' => $revision,
                'entities' => $this->auditReader->findEntitiesChangedAtRevision($revision->getRev()),
            ];
        }

        return $this->renderResponse($template, [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'revisions' => $revisions,
        ], $response);
    }

    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'limit' => 10,
            'template' => '@SonataDoctrineORMAdmin/Block/block_audit.html.twig',
        ]);
    }
}
