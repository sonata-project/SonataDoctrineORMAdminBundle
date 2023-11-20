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

namespace Sonata\DoctrineORMAdminBundle\Tests\App;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Sonata\AdminBundle\SonataAdminBundle;
use Sonata\BlockBundle\SonataBlockBundle;
use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle;
use Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle;
use Sonata\Exporter\Bridge\Symfony\SonataExporterBundle;
use Sonata\Form\Bridge\Symfony\SonataFormBundle;
use Sonata\Twig\Bridge\Symfony\SonataTwigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
            new FrameworkBundle(),
            new KnpMenuBundle(),
            new SecurityBundle(),
            new SonataAdminBundle(),
            new SonataBlockBundle(),
            new SonataExporterBundle(),
            new SonataDoctrineBundle(),
            new SonataDoctrineORMAdminBundle(),
            new SonataFormBundle(),
            new SonataTwigBundle(),
            new TwigBundle(),
            new DoctrineFixturesBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return $this->getBaseDir().'cache';
    }

    public function getLogDir(): string
    {
        return $this->getBaseDir().'log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(sprintf('%s/config/routes.yaml', $this->getProjectDir()));
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('app.base_dir', $this->getBaseDir());

        $loader->load(__DIR__.'/config/config.yml');

        if (!class_exists(IsGranted::class)) {
            $loader->load(__DIR__.'/config/config_symfony_v5.yml');
        }

        $loader->load(__DIR__.'/config/services.php');
    }

    private function getBaseDir(): string
    {
        return sys_get_temp_dir().'/sonata-doctrine-orm-admin-bundle/var/';
    }
}
