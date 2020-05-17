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
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AuditBlockService extends AbstractBlockService
{
    /**
     * @var AuditReader
     */
    protected $auditReader;

    public function __construct(Environment $templating, AuditReader $auditReader)
    {
        parent::__construct($templating);

        $this->auditReader = $auditReader;
    }

    public function execute(BlockContextInterface $blockContext, ?Response $response = null): Response
    {
        $revisions = [];

        foreach ($this->auditReader->findRevisionHistory($blockContext->getSetting('limit'), 0) as $revision) {
            $revisions[] = [
                'revision' => $revision,
                'entities' => $this->auditReader->findEntitiesChangedAtRevision($revision->getRev()),
            ];
        }

        return $this->renderResponse($blockContext->getTemplate(), [
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'revisions' => $revisions,
        ], $response);
    }

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block): void
    {
    }

    public function getName()
    {
        return 'Audit List';
    }

    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'limit' => 10,
            'template' => '@SonataDoctrineORMAdmin/Block/block_audit.html.twig',
        ]);
    }
}
