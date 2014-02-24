<?php


namespace Sonata\DoctrineORMAdminBundle\Generator;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class DoctrineORMAdminGenerator extends Generator
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $classPath;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getClassPath()
    {
        return $this->classPath;
    }

    /**
     * @param BundleInterface $bundle
     * @param $entity
     * @param ClassMetadataInfo $metadata
     * @throws \RuntimeException
     */
    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata)
    {
        $parts       = explode('\\', $entity);
        $entityClass = array_pop($parts);

        $this->className = $entityClass.'Admin';
        $dirPath         = $bundle->getPath().'/Admin';
        $this->classPath = $dirPath.'/'.str_replace('\\', '/', $entity).'Admin.php';

        if (file_exists($this->classPath)) {
            throw new \RuntimeException(sprintf('Unable to generate the %s form class as it already exists under the %s file', $this->className, $this->classPath));
        }

        $parts = explode('\\', $entity);
        array_pop($parts);

        $this->renderFile('Admin/Admin.php.twig', $this->classPath, array(
                'fields'           => $this->getFieldsFromMetadata($metadata),
                'namespace'        => $bundle->getNamespace(),
                'entity_namespace' => implode('\\', $parts),
                'entity_class'     => $entityClass,
                'bundle'           => $bundle->getName(),
                'admin_class'       => $this->className,
            ));
    }

    /**
     * copied from \Sensio\Bundle\GeneratorBundle\Generator\DoctrineFormGenerator
     *
     * @param ClassMetadataInfo $metadata
     * @return array
     */
    private function getFieldsFromMetadata(ClassMetadataInfo $metadata)
    {
        $fields = (array) $metadata->fieldNames;

        // Remove the primary key field if it's not managed manually
        if (!$metadata->isIdentifierNatural()) {
            $fields = array_diff($fields, $metadata->identifier);
        }

        foreach ($metadata->associationMappings as $fieldName => $relation) {
            if ($relation['type'] !== ClassMetadataInfo::ONE_TO_MANY) {
                $fields[] = $fieldName;
            }
        }

        return $fields;
    }
}