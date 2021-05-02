.. index::
    double: Reference; Export / DataSource

Export / DataSource
===================

When using an admins export feature you might want to modify how dates and times are exported.
This is done by calling ``setDateTimeFormat`` on the data source iterator.

Here's one way to do it:

1. Decorate the default Sonata\DoctrineORMAdminBundle\Exporter\DataSource with your own and call ``setDateTimeFormat`` there.::

      <?php
      
      namespace App\Service\Admin;
      
      use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
      use Sonata\AdminBundle\Exporter\DataSourceInterface;
      use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;
      use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
      use Sonata\Exporter\Source\SourceIteratorInterface;
      
      class DecoratingDataSource implements DataSourceInterface
      {
          private DataSource $dataSource;
          
          public function __construct(DataSource $dataSource)
          {
              $this->dataSource = $dataSource;
          }
          
          public function createIterator(ProxyQueryInterface $query, array $fields): SourceIteratorInterface
          {
              /** @var DoctrineORMQuerySourceIterator $iterator */
              $iterator = $this->dataSource->createIterator($query, $fields);
              
              $iterator->setDateTimeFormat('Y-m-d H:i:s');              
              
              return $iterator;
          }
      }


2. Add the your service in the ``config/services.yaml`` definition.::

      services:
          ...
          App\Service\Admin\DecoratingDataSource:
              decorates: 'sonata.admin.data_source.orm'


