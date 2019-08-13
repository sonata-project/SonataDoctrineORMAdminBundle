<?php
declare(strict_types=1);

namespace Sonata\DoctrineORMAdminBundle\Tests\Fixtures\Entity;

class Product
{
    /**
     * @var ProductId
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @param ProductId $id
     * @param string    $name
     */
    public function __construct(ProductId $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return ProductId
     */
    public function getId(): ProductId
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
