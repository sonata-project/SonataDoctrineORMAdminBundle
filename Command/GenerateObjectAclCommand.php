<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Command;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Sonata\AdminBundle\Security\Handler\AclSecurityHandler;
use Sonata\AdminBundle\Exception\ModelManagerException;

class GenerateObjectAclCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('sonata:admin:generate-object-acl');
        $this->setDescription('Install ACL for the objects of the Admin Classes');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $output->writeln('Welcome to the AdminBundle object ACL generator');
        $output->writeln(array(
                '',
                'This command helps you generate ACL entities for the objects handled by the AdminBundle.',
                '',
                'Foreach Admin, you will be asked to generate the object ACL entities',
                'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment> if you want to set an object owner.',
                ''
        ));

        $aclProvider = $this->getContainer()->get('security.acl.provider');


        foreach ($this->getContainer()->get('sonata.admin.pool')->getAdminServiceIds() as $id) {

            try {
                $admin = $this->getContainer()->get($id);
            } catch (\Exception $e) {
                $output->writeln('<error>Warning : The admin class cannot be initiated from the command line</error>');
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                continue;
            }

            if (!$dialog->askConfirmation($output, sprintf("<question>Generate ACLs for the object instances handled by \"%s\"?</question>\n", $id), false)) {
                continue;
            }

            $securityIdentity = null;
            if ($dialog->askConfirmation($output,"<question>Set an object owner?</question>\n", false)) {
                $username = $dialog->askAndValidate($output, 'Please enter the username: ', 'Sonata\DoctrineORMAdminBundle\Command\Validators::validateUsername');
                list($userBundle, $userEntity) = $dialog->askAndValidate($output, 'Please enter the User Entity shortcut name: ', 'Sonata\DoctrineORMAdminBundle\Command\Validators::validateEntityName');

                // Entity exists?
                try {
                    $userEntityClass = $this->getContainer()->get('doctrine')->getEntityNamespace($userBundle).'\\'.$userEntity;
                } catch (\Exception $e) {
                    $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                    continue;
                }
                $securityIdentity = new UserSecurityIdentity($username, $userEntityClass);
            }

            $securityHandler = $admin->getSecurityHandler();
            if (!$securityHandler instanceof AclSecurityHandler) {
                $output->writeln('Admin class is not configured to use ACL : <info>ignoring</info>');
                continue;
            }

            $em = $admin->getModelManager()->getEntityManager();
            $datagrid = $admin->getDatagrid();
            $datagrid->buildPager();
            $query = $datagrid->getQuery();

            $query->setFirstResult(null);
            $query->setMaxResults(null);

            $count = 0;
            $countUpdated = 0;
            $countAdded = 0;

            try {
                $batchSize = 20;
                $batchSizeOutput = 200;
                $i = 0;
                $oids = array();
                foreach ($query->getQuery()->iterate() as $row) {
                    $oids[] = ObjectIdentity::fromDomainObject($row[0]);

                    // detach from Doctrine, so that it can be Garbage-Collected immediately
                    $em->detach($row[0]);

                    if ((++$i % $batchSize) == 0) {

                        list($batchAdded, $batchUpdated) = $this->configureObjectAcl($admin, $aclProvider, $oids, $securityHandler, $securityIdentity);
                        $countAdded += $batchAdded;
                        $countUpdated += $batchUpdated;
                        $oids = array();
                    }

                    if ((++$i % $batchSizeOutput) == 0) {
                        $output->writeln(sprintf('   - generated class ACEs for %s objects (added %s, updated %s)', $count, $countAdded, $countUpdated));
                    }

                    $count++;
                }

                if (count($oids) > 0) {
                    list($batchAdded, $batchUpdated) = $this->configureObjectAcl($admin, $aclProvider, $oids, $securityHandler, $securityIdentity);
                    $countAdded += $batchAdded;
                    $countUpdated += $batchUpdated;
                }
            } catch ( \PDOException $e ) {
                throw new ModelManagerException('', 0, $e);
            }

            $output->writeln(sprintf('   - [TOTAL] generated class ACEs for %s objects (added %s, updated %s)', $count, $countAdded, $countUpdated));
        }
    }

    protected function configureObjectAcl(AdminInterface $admin, MutableAclProviderInterface $aclProvider, array $oids, SecurityHandlerInterface $securityHandler, UserSecurityIdentity $securityIdentity = null)
    {
        $countAdded = 0;
        $countUpdated = 0;

        // find object ACLs
        try {
            $acls = $aclProvider->findAcls($oids);
        } catch(\Exception $e) {
            if ($e instanceof NotAllAclsFoundException) {
                $acls = $e->getPartialResult();
            } elseif ($e instanceof AclNotFoundException) {
                // if only one oid, this error is thrown
                $acls = new \SplObjectStorage();
            } else {
                throw $e;
            }
        }


        foreach ($oids as $oid) {
            if ($acls->contains($oid)) {
                $acl = $acls->offsetGet($oid);
                $action = 'update';
                $countUpdated++;
            } else {
                $acl = $aclProvider->createAcl($oid);
                $action = 'add';
                $countAdded++;
            }

            if (!is_null($securityIdentity)) {
                // add object owner
                $securityHandler->addObjectOwner($acl, $securityIdentity);
            }

            $securityHandler->addObjectClassAces($acl, $securityHandler->buildSecurityInformation($admin));
            $aclProvider->updateAcl($acl);
        }

        return array($countAdded, $countUpdated);
    }
}