.. index::
    double: Reference; Proxy Query

Doctrine ORM Proxy Query
========================

The ``ProxyQuery`` object is used to add missing features from the original `Doctrine Query` builder:

* ``execute`` method - no need to call the ``getQuery()`` method,
* add sort by and sort order options,
* add preselect id query on left join query, so a limit query will be only
  applied on the left statement and not on the full select statement.
  This simulates the original Doctrine 1 behavior.
* By default, Sonata will use the ``DISTINCT`` SQL keyword when fetching
  the identifiers of the entities that will be displayed in the listing,
  to avoid duplicates in some cases. Sonata cannot detect whether or not
  you need ``DISTINCT``, but lets you remove that keyword in case it
  causes performance issues and you are sure there will be no duplicates.
  To do so, simply call ``setDistinct(false)``::

      use Sonata\AdminBundle\Datagrid\ORM\ProxyQuery;

      $queryBuilder = $this->em->createQueryBuilder();
      $queryBuilder->from('Post', 'p');

      $proxyQuery = new ProxyQuery($queryBuilder);
      $proxyQuery->leftJoin('p.tags', 't');
      $proxyQuery->setSortBy('name');
      $proxyQuery->setMaxResults(10);

      $results = $proxyQuery->execute();
