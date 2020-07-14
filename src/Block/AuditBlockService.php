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
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    /**
     * NEXT_MAJOR: Allow only Environment|EngineInterface for argument 1 and AuditReader for argument 2.
     *
     * @param Environment|EngineInterface|string $templatingOrDeprecatedName
     * @param EngineInterface|AuditReader        $templatingOrAuditReader
     */
    public function __construct($templatingOrDeprecatedName, object $templatingOrAuditReader, ?AuditReader $auditReader = null)
    {
        if ($templatingOrAuditReader instanceof EngineInterface) {
            @trigger_error(sprintf(
                'Passing %s as argument 2 to %s() is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21'
                .' and will throw a \TypeError in version 4.0. You must pass an instance of %s instead.',
                EngineInterface::class,
                __METHOD__,
                AuditReader::class
            ), E_USER_DEPRECATED);

            if (null === $auditReader) {
                throw new \TypeError(sprintf(
                    'Passing null as argument 3 to %s() is not allowed when %s is passed as argument 2.'
                    .' You must pass an instance of %s instead.',
                    __METHOD__,
                    EngineInterface::class,
                    AuditReader::class
                ));
            }

            parent::__construct($templatingOrDeprecatedName, $templatingOrAuditReader);

            $this->auditReader = $auditReader;
        } elseif ($templatingOrAuditReader instanceof AuditReader) {
            if (!$templatingOrDeprecatedName instanceof Environment
                && !$templatingOrDeprecatedName instanceof EngineInterface
            ) {
                throw new \TypeError(sprintf(
                    'Argument 1 passed to %s() must be either an instance of %s or preferably %s, %s given.',
                    __METHOD__,
                    EngineInterface::class,
                    Environment::class,
                    \is_object($templatingOrDeprecatedName)
                        ? 'instance of '.\get_class($templatingOrDeprecatedName)
                        : \gettype($templatingOrDeprecatedName)
                ));
            }

            parent::__construct($templatingOrDeprecatedName);

            $this->auditReader = $templatingOrAuditReader;
        } else {
            throw new \TypeError(sprintf(
                'Argument 2 passed to %s() must be either an instance of %s or preferably %s, instance of %s given.',
                __METHOD__,
                EngineInterface::class,
                AuditReader::class,
                \get_class($templatingOrAuditReader)
            ));
        }
    }

    public function execute(BlockContextInterface $blockContext, ?Response $response = null)
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

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
    }

    public function getName()
    {
        return 'Audit List';
    }

    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'limit' => 10,
            'template' => '@SonataDoctrineORMAdmin/Block/block_audit.html.twig',
        ]);
    }
}
