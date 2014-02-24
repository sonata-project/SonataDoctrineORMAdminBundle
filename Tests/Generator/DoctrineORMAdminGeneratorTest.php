<?php


namespace Sonata\DoctrineORMAdminBundle\Tests\Generator;


use Sonata\DoctrineORMAdminBundle\Generator\DoctrineORMAdminGenerator;

class DoctrineORMAdminGeneratorTest extends \PHPUnit_Framework_TestCase
{
    private function cleanGeneratedFile()
    {
        if (is_dir(__DIR__.'/Admin')) {
            @unlink(__DIR__.'/Admin/DummyAdmin.php');
            @rmdir(__DIR__.'/Admin');
        }
    }

    public function testGenerate()
    {
        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');

        $generator = new DoctrineORMAdminGenerator($filesystem);
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->once())->method('getPath')->will($this->returnValue(__DIR__));
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $metadata->fieldNames = array('title', 'body');
        $metadata->expects($this->once())->method('isIdentifierNatural')->will($this->returnValue(false));

        $generator->setSkeletonDirs(array(__DIR__.'/../../Resources/skeleton'));

        $dummyEntity = 'Dummy';
        $generator->generate($bundle, $dummyEntity, $metadata);
    }

    public function setUp()
    {
        $this->cleanGeneratedFile();
    }

    public function tearDown()
    {
        $this->cleanGeneratedFile();
    }
}
 