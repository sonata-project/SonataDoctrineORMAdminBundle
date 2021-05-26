# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [3.34.2](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.34.1...3.34.2) - 2021-05-26
### Fixed
- [[#1444](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1444)] Fixed issue where pagination of large dataset would take very long time or cause database engine  to swap even for simplest queries without joins. ([@alfabetagama](https://github.com/alfabetagama))

## [3.34.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.34.0...3.34.1) - 2021-05-18
### Fixed
- [[#1439](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1439)] Bind arbitrary params as part of a value expression in the query filter at `Filter::applyWhere()` with PostgreSQL. ([@phansys](https://github.com/phansys))

## [3.34.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.33.0...3.34.0) - 2021-05-02
### Changed
- [[#1425](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1425)] `ProxyQuery::execute()` is now returning a Paginator instead of an array. ([@VincentLanglet](https://github.com/VincentLanglet))

### Deprecated
- [[#1427](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1427)] `templates.form` and `templates.filter` config ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1428](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1428)] Deprecated not adding `FilterData` as type declaration of argument 4 in the callable passed to `CallbackFilter` ([@franmomu](https://github.com/franmomu))

### Fixed
- [[#1427](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1427)] Always merge `SonataDoctrineORMAdmin` form and filter templates. ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1427](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1427)] Using `sonata_admin` configuration. ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1425](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1425)] Support for fetch join with simple pager. ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.33.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.32.1...3.33.0) - 2021-04-19
### Added
- [[#1416](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1416)] "force_case_insensitivity" option to `StringFilter` in order to force the database to ignore the case sensitivity when matching filters. ([@phansys](https://github.com/phansys))

### Changed
- [[#1395](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1395)] Default value for the "case_sensitive" option from `true` to `null` in `StringFilter`. ([@phansys](https://github.com/phansys))

### Deprecated
- [[#1416](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1416)] "case_sensitive" option in `StringFilter`. ([@phansys](https://github.com/phansys))

### Fixed
- [[#1408](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1408)] Allow to decorate EntityManager ([@michkinn](https://github.com/michkinn))
- [[#1414](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1414)] Return type for `ModelManager::getModelIdentifier()`. ([@phansys](https://github.com/phansys))
- [[#1399](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1399)] Fixed triggering always deprecation when calling `ModelManager::getDefaultSortValues()` method ([@franmomu](https://github.com/franmomu))

## [3.32.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.32.0...3.32.1) - 2021-04-06
### Fixed
- [[#1393](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1393)] Added missing filter declaration in the config ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.32.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.31.0...3.32.0) - 2021-03-30
### Added
- [[#1355](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1355)] `ModelManager::reverseTransform()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] Added `FieldDescriptionFactory` class ([@VincentLanglet](https://github.com/VincentLanglet))

### Deprecated
- [[#1341](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1341)] Deprecated the `Sonata\DoctrineORMAdminBundle\Filter\EmptyFilter` service since its class is already deprecated since version 3.27 ([@dmaicher](https://github.com/dmaicher))
- [[#1355](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1355)] `ModelManager::modelReverseTransform()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] `AbstractTypeGuesser` class ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] `FilterTypeGuesser::guessType()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] `TypeGuesser::guessType()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] `ModelManager:: getParentMetadataForProperty()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] `ModelManager:: getNewFieldDescriptionInstance()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1350](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1350)] `ModelManager:: getModelInstance()` ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1376](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1376)] Fixed triggering deprecation because audit reader is not tagged ([@franmomu](https://github.com/franmomu))
- [[#1374](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1374)] Fixed deprecations about not implementing `FieldDescriptionInterface` methods ([@franmomu](https://github.com/franmomu))
- [[#1358](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1358)] Resulting `WHERE` clause from `Filter::applyWhere()` when using `OR` conditions on queries that already have previous conditions ([@phansys](https://github.com/phansys))
- [[#1368](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1368)] Fetch join queries for Pager ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1368](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1368)] Pager when using entity inheritance ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1365](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1365)] Missing argument 2 in calls to `trigger_error()` ([@phansys](https://github.com/phansys))

## [3.31.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.30.0...3.31.0) - 2021-03-11
### Added
- [[#1335](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1335)] Support for symfony/config:^5.2 ([@phansys](https://github.com/phansys))
- [[#1335](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1335)] Support for symfony/dependency-injection:^5.2 ([@phansys](https://github.com/phansys))
- [[#1335](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1335)] Support for symfony/http-foundation:^5.2 ([@phansys](https://github.com/phansys))
- [[#1319](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1319)] `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface::getDoctrineQuery()` ([@VincentLanglet](https://github.com/VincentLanglet))

### Changed
- [[#1336](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1336)] Update constraint for "sonata-project/admin-bundle" from ^3.88 to ^3.89.1 ([@phansys](https://github.com/phansys))

### Deprecated
- [[#1333](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1333)] Deprecate passing arguments to `ProxyQuery::execute()` method ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1326](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1326)] Not passing a `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface` as argument 2 of `ModelManager::addIdentifiersToQuery()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1326](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1326)] Not passing a `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface` as argument 2 of `ModelManager::batchDelete()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1319](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1319)] Class `Sonata\DoctrineORMAdminBundle\Datagrid\OrderByToSelectWalker` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1319](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1319)] `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery::getFixedQueryBuilder()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1319](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1319)] `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery::getSingleScalarResult()` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1323](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1323)] Not passing a `ManagerRegistry` as first argument of `ObjectAclManipulator` ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1319](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1319)] Do not display multiple times the same row in the admin list and the export list ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.30.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.29.0...3.30.0) - 2021-02-24
### Added
- [[#1285](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1285)] Added support for PHP 8.x ([@Yozhef](https://github.com/Yozhef))

### Deprecated
- [[#1291](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1291)] Passing another `type` value to a filter than an integer handled ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1314](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1314)] Unavoidable deprecation about the `code` option ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1247](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1247)] `ChoiceFilter` returns `null` values when used with the type `NOT_EQUAL` ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.29.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.28.0...3.29.0) - 2021-02-08
### Deprecated
- [[#1292](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1292)] Extending `ProxyQuery` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1292](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1292)] Extending `DataSource` ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1287](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1287)] Fixed `CountFilter` ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.28.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.27.0...3.28.0) - 2021-01-26
### Added
- [[#1280](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1280)] Added `CountFilter`. ([@rgrassian](https://github.com/rgrassian))

### Changed
- [[#1268](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1268)] Use Doctrine ORM Paginator to count in Pager. ([@VincentLanglet](https://github.com/VincentLanglet))

### Deprecated
- [[#1268](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1268)] `Pager::CONCAT_SEPARATOR` ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1265](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1265)] Do not provide a default `null` `field_type` option for Filter ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1268](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1268)] Support of composite key for computeNbResult ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.27.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.26.0...3.27.0) - 2021-01-17
### Added
- [[#1262](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1262)] Added Pager::getCurrentPageResults() ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1257](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1257)] Implemented `Sonata\AdminBundle\Datagrid\PagerInterface::countResults()` ([@dmaicher](https://github.com/dmaicher))
- [[#1234](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1234)] Added `Sonata\DoctrineORMAdminBundle\Filter\NullFilter` ([@pbories](https://github.com/pbories))
- [[#1218](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1218)] Added `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1212](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1212)] Added `DataSourceInterface` implementation ([@VincentLanglet](https://github.com/VincentLanglet))

### Changed
- [[#1259](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1259)] Code formatting in views and change from `<a>` with empty href to button ([@axzx](https://github.com/axzx))
- [[#1255](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1255)] Changing access checking in views (isGranted to hasAccess) ([@axzx](https://github.com/axzx))
- [[#1241](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1241)] When using embedded fields or fake field 'foo.bar' (with custom getters in the entity), `FieldDescription::fieldName` is changed from `bar` to the correct value `foo.bar` ([@VincentLanglet](https://github.com/VincentLanglet))

### Deprecated
- [[#1262](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1262)] Deprecated Pager::getResults() ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1257](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1257)] `Sonata\DoctrineORMAdminBundle\Datagrid\Pager::computeNbResult()` ([@dmaicher](https://github.com/dmaicher))
- [[#1257](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1257)] `Sonata\DoctrineORMAdminBundle\Datagrid\Pager::getNbResults()` ([@dmaicher](https://github.com/dmaicher))
- [[#1257](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1257)] `Sonata\DoctrineORMAdminBundle\Datagrid\Pager::setNbResults()` ([@dmaicher](https://github.com/dmaicher))
- [[#1234](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1234)] Deprecated `Sonata\DoctrineORMAdminBundle\Filter\EmptyFilter` ([@pbories](https://github.com/pbories))
- [[#1232](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1232)] Deprecated `ModelManager::getMetadata()` method ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1232](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1232)] Deprecated `ModelManager::hasMetadata()` method ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1211](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1211)] Deprecate `Sonata\DoctrineORMAdminBundle\Model\ModelManager::modelTransform()` with no replacement ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1211](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1211)] Deprecate `Sonata\DoctrineORMAdminBundle\Model\ModelManager::getDefaultPerPageOptions()` with no replacement ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1211](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1211)] Deprecate `Sonata\DoctrineORMAdminBundle\Model\ModelManager::getDefaultSortValues()` with no replacement ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1211](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1211)] Deprecate `Sonata\DoctrineORMAdminBundle\Model\ModelManager::getDataSourceIterator()` with no replacement ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1199](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1199)] Deprecate passing an instance of `ProxyQueryInterface` which is not an instance of `Sonata\DoctrineORMAdminBundle\Datagrid::ProxyQuery` as argument 1 to the `Sonata\DoctrineORMAdminBundle\Filter\Filter::filter()` method ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1248](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1248)] Unavoidable deprecation in Pager ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1254](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1254)] Fix for checking if delete button is to be displayed ([@axzx](https://github.com/axzx))
- [[#1241](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1241)] Support for embedded and custom getters by the FieldDescription ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.26.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.25.0...3.26.0) - 2020-11-19
### Added
- [[#1207](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1207)] Added an option `inverse` for the `EmptyFilter` filter ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1120](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1120)] Allow empty string for filtering ([@peter-gribanov](https://github.com/peter-gribanov))

## [3.25.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.24.0...3.25.0) - 2020-11-15
### Added
- [[#1202](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1202)] Added "Not equal" filter for `StringFilter` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1190](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1190)] Support for `\DateTimeImmutable` at `AbstractDateFilter::filter()` ([@phansys](https://github.com/phansys))
- [[#1166](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1166)] Check to guarantee that argument 3 passed to `ModelManager::addIdentifiersToQuery()` is not an empty array ([@phansys](https://github.com/phansys))

### Deprecated
- [[#1170](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1170)] Instantiate a FieldDescription without passing the name as first argument ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1159](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1159)] Using a callback filter with a callback option which does not return a boolean ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1197](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1197)] `FormContractor::getDefaultOptions()` passes `collection_by_reference` option instead of `by_reference` to `AdminType` in order to respect the new API ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1189](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1189)] Setting wrong date and time references at `AbstractDateFilter::filter()` ([@phansys](https://github.com/phansys))
- [[#1136](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1136)] `TypeError` thrown by `explode()` when receiving non string values as argument 2 from argument 3 at `ModelManager::addIdentifiersToQuery()` ([@phansys](https://github.com/phansys))

## [3.24.0](sonata-project/SonataDoctrineORMAdminBundle/compare/3.23.0...3.24.0) - 2020-10-08
### Added
- [[#1142](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1142)] `FormContractor::getDefaultOptions()` pass `by_reference` from `CollectionType` to `AdminType` ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1127](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1127)] Add more phpdoc ([@core23](https://github.com/core23))
- [[#1113](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1113)] `ModelManager::supportsQuery()` method ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1117](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1117)] Allow `doctrine/persistence` 2 ([@franmomu](https://github.com/franmomu))

### Deprecated
- [[#1113](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1113)] Calling `executeQuery()` on something else than an instance of `Doctrine\ORM\QueryBuilder` or `Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery` ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1128](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1128)] Fix null error in `ObjectAclManipulator` ([@core23](https://github.com/core23))

## [3.23.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.22.0...3.23.0) - 2020-09-13
### Deprecated
- [[#1109](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1109)] Deprecate ModelManager collections methods. ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1109](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1109)] Deprecate ModelManager::getPaginationParameters(). ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1109](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1109)] Deprecate ModelManager::getSortParameters(). ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.22.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.21.0...3.22.0) - 2020-08-29
### Added
- [[#1091](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1091)] Added support for symfony/options-resolver:^5.1 ([@phansys](https://github.com/phansys))
- [[#1091](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1091)] Added support for symfony/property-access:^5.1 ([@phansys](https://github.com/phansys))
- [[#1100](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1100)] Add support for Twig 3 ([@willemverspyck](https://github.com/willemverspyck))
- [[#1023](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1023)] Add support for SonataBlockBundle 4.0 ([@wbloszyk](https://github.com/wbloszyk))

### Changed
- [[#1077](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1077)] Use `deprecated` tag instead of `sonata_template_deprecate` to not throw unwanted deprecation notices ([@franmomu](https://github.com/franmomu))

### Deprecated
- [[#1082](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1082)] Deprecate ModelManager::getParentFieldDescription with no replacement ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1078](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1078)] Deprecate `ModelManager::camelize` ([@VincentLanglet](https://github.com/VincentLanglet))

### Removed
- [[#1077](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1077)] Support for Twig 1.x ([@franmomu](https://github.com/franmomu))

## [3.21.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.20.0...3.21.0) - 2020-07-14
### Added
- [[#979](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/979)]  Add new filter for empty fields ([@core23](https://github.com/core23))

### Deprecated
- [[#1061](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1061)] `format` option of the `StringFilter`. ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#1067](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1067)] Passing a string as argument 1 when instantiating Sonata\DoctrineORMAdminBundle\Block\AuditBlockService ([@VincentLanglet](https://github.com/VincentLanglet))

## [3.20.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.19.0...3.20.0) - 2020-06-30
### Added
- [[#1057](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1057)]
  Add `StringFilter` support for `START_WITH` and `END_WITH` operator
([@napestershine](https://github.com/napestershine))
- [[#1049](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1049)]
  Added `FieldDescription::getTargetModel()`.
([@phansys](https://github.com/phansys))

### Deprecated
- [[#1049](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1049)] Deprecated passing `null` as argument 2 for `ModelManager::find()`; ([@phansys](https://github.com/phansys))
- [[#1049](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1049)] Deprecated passing `null` as argument 1 for `ModelManager::getNormalizedIdentifier()`; ([@phansys](https://github.com/phansys))
- [[#1049](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1049)] Deprecated passing objects which are in state 2 (new) or 4 (removed) as argument 1 for `ModelManager::getNormalizedIdentifier()`; ([@phansys](https://github.com/phansys))
- [[#1049](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1049)] Deprecated passing other type than `object` as argument 1 for `ModelManager::getUrlSafeIdentifier()`; ([@phansys](https://github.com/phansys))
- [[#1049](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1049)] Deprecated `FieldDescription::getTargetEntity()` in favor of `FieldDescription::getTargetModel()`. ([@phansys](https://github.com/phansys))

## [3.19.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.18.0...3.19.0) - 2020-06-26
### Changed
- [[#1055](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1055)]
  `decimal` and `float` type use the `float` template if no `number` template
exists ([@VincentLanglet](https://github.com/VincentLanglet))

### Fixed
- [[#1055](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1055)]
  `one_to_one`, `one_to_many`, `many_to_one` and `many_to_many` type are
correctly using the template defined in your config instead of the Sonata one.
([@VincentLanglet](https://github.com/VincentLanglet))

### Removed
- [[#1048](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/pull/1048)]
  Remove SonataCoreBundle dependencies
([@wbloszyk](https://github.com/wbloszyk))

## [3.18.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.17.1...3.18.0) - 2020-06-02
### Added
- Added direct dependency against "twig/twig".
- Added `ModelManager::getDefaultPerPageOptions`
- `ArrayFilter` which supports `@ORM\Column(type="array")`

### Fixed
- Fixed usage of deprecated Twig syntax `for..if`.
- StringFilter now correctly takes the `case_sensitive` option into account
  when the operator is `=`.

## [3.17.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.17.0...3.17.1) - 2020-04-21
### Fixed
Typo in AbstractDateFilter, `DateRangeOperatorType::TYPE_EQUAL` should have been
`DateOperatorType::TYPE_EQUAL`.
## [3.17.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.16.0...3.17.0) - 2020-04-11
### Fixed
- Fixed weak check at `ModelManager::getNormalizedIdentifier()`.

### Deprecated
- Deprecate `getModelIdentifier` from `ModelManager`

## [3.16.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.15.0...3.16.0) - 2020-04-02
### Added
- `@method` annotation for `ProxyQuery`

### Fixed
- Fixed returning `void` in `ModelManager::getNormalizedIdentifier()`, which is
intended to return a value or `null`.
- Removed deprecated usage of admin `FormType` constants
- Concat value of complex primary key for correct calculate total pages in `Datagrid`

### Removed
- Drop support of php 7.1

## [3.15.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.14.0...3.15.0) - 2020-03-16
### Added
- Added support for guessing the show type of `simple_array` fields.
- Allow null to be selected in ChoiceFilter
- Allow `_sort_by` filter to not be initially defined.
- `sonata.admin.manager` tag to `sonata.admin.manager.orm` service.

### Fixed
- The `_sort_by_ ` datagrid value is properly applied before any custom `orderBy`.
- Crash when entity has many identifiers and one of the not last identifiers is an entity.

## [3.14.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.13.0...3.14.0) - 2020-02-04
### Fixed
- crash in `computeNbResult` where `groupBy` was used
- deprecations from `doctrine/persistence`
- Avoid to call not accessible `toString()` methods.

### Changed
- Disabled validation group in `Builder/DatagridBuilder::getBaseDatagrid()`

### Changed
- Added check and call of `toString` and `__toString` when calling
  `getValueFromType` on value-object such as Uuid

## [3.13.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.12.0...3.13.0) - 2019-12-23
### Fixed
- Fix ProxyQuery::getQueryBuilder() return type.

### Changed
`operator_type` and `operator_options` are overridable for the provided Filters

### Removed
- Support for Symfony < 3.4
- Support for Symfony >= 4, < 4.2

## [3.12.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.11.0...3.12.0) - 2019-11-23
### Added
- Added support for Doctrine-Bundle 2.0

### Fixed
- Do not return exception if `Pager->computeNbResult` has no result

## [3.11.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.10.0...3.11.0) - 2019-11-03
### Fixed
- Fix a break BC error

### Changed
Create const for operator choices in Filter classes

## [3.10.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.9.0...3.10.0) - 2019-07-20
### Added
- Don't hide edit button `sonata_type_model_list_widget` if there is no value

### Fixed
- Use a more reliable method for represent composite identifiers in a string

## [3.9.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.8.3...3.9.0) - 2019-04-17
### Added
- Added support for protected (no public constructor) entity creation

## [3.8.3](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.8.2...3.8.3) - 2019-02-28
### Fixed
- Deprecations about core bundle forms
- Exception on `StringFilter` with null values
- autocomplete action no longer advertises for more items when there are actually none

## [3.8.2](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.8.1...3.8.2) - 2019-02-04
### Fixed
- `Sonata\DoctrineORMAdminBundle\Datagrid\Pager::computeNbResult()` now returns an integer, not a string
- Composite key pagination throwing exception

## [3.8.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.8.0...3.8.1) - 2019-01-23
### Fixed
TypeError with explode in ModelManager

## [3.8.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.7.0...3.8.0) - 2019-01-20

### Added
- Compatibility with `sonata-project/exporter` 2

### Removed
- support for php 5 and php 7.0

## [3.7.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.6.3...3.7.0) - 2018-12-29
### Added
- Offer support of id string resolution for entity id object where
`__toString()` will be used if provided. (This also fixes the issue where the
id fails to display when the binary / byte datatype is used as primary key,
e.g., UUIDBinaryType (aka: InnoDB Optimised Binary UUIDs))
- Added possibility to make `StringFilter` case-insensitive

### Fixed
- Fix using the new collection type namespace
- Fix deprecation for symfony/config 4.2+
- Fix `Twig_Error_Runtime` "Key "associationAdmin" for array with keys
translationDomain, associationadmin, options" does not exist."

## [3.6.3](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.6.2...3.6.3) - 2018-10-25
### Fixed
- `sonata.admin.manipulator.acl.object.orm` is now public

## [3.6.2](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.6.1...3.6.2) - 2018-10-01
### Fixed
- Composite key not working
- Block `sonata_type_model_list_widget` in template `Form/form_admin_fields.html.twig` now determines the object identifier correctly when building a link to the associated admin

## [3.6.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.6.0...3.6.1) - 2018-06-04

### Fixed

- Fix FieldDescription for multi-level embedded properties
- marked `sonata.admin.manager.orm` as public service

## [3.6.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.5.1...3.6.0) - 2018-04-23
### Added
- Added `ProxyQuery::setDistinct` and `ProxyQuery::isDistinct`.

### Changed
- `Pager` use `CountWalker` for get count.

## [3.5.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.5.0...3.5.1) - 2018-04-10
### Fixed
- Now it is possible to use entities with arguments on the constructor on the Collection and Admin types.

## [3.5.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.4.2...3.5.0) - 2018-04-09
### Added
- Support for "datetime_immutable", "datetimetz_immutable", "date_immutable" and "time_immutable" Doctrine types at `TypeGuesser::guessType()` and `FilterTypeGuesser::guessType()`.
- Added json_array to type guesser

### Changed
- Added doctrine/doctrine-bundle to composer.json

### Fixed
- embedded fields not working as filters
- "nl2br() expects parameter 1 to be string, object given" error caused at `base_show_field.html.twig`.

## [3.4.2](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.4.1...3.4.2) - 2018-02-08
### Changed
- Switch all templates references to Twig namespaced syntax
- Switch from templating service to sonata.templating

### Fixed
- Symfony 3.4 deprecation notice about getting private service AuditReader from the container
- Hide selects added by OrderByToSelectWalker from hydration
- Add orderBy field to select list for DataSourceIterator

### Security
- `setSortOrder` input is now validated

## [3.4.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.4.0...3.4.1) - 2018-01-18
### Fixed
- typo in ListBuilder

## [3.4.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.3.0...3.4.0) - 2018-01-18
### Changed
- Switched to templates from SonataAdminBundle

### Deprecated
- Association templates

### Fixed
- Fixed invalid PathExpression error in ProxyQuery

## [3.3.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.2.0...3.3.0) - 2017-12-16
### Added
- Added refresh of edit button URL if item was replaced by add/list action

### Fixed
- Allow to `add` a new Model even if one is already selected
- Fixed invalid PathExpression error in ProxyQuery
- Issue with edit button always showing initial item in popup
- Replaced FQCN strings with `::class` constants
- deprecation about `Doctrine\ORM\Mapping\ClassMetadataInfo`

## [3.2.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.7...3.2.0) - 2017-11-30
### Added
- Added edit button that opens in dialog instead of add if there is object already in sonata type model list
- Added `ProxyQuery::setHint` which allow to pass Query hint in createQuery

### Changed
- Change minimum doctrine/orm version to 2.4.5 because QueryBuilder bug on PHP7 and HHVM

### Fixed
- don't display fields that are missing in child classes
- warning about deprecate "e" modifier for `preg_replace`
- Fix sorting by multiple columns in custom createQuery in PostgreSQL and MySQL 5.7
- compatibility with Symfony 4
- Fix CollectionType on Symfony 3 when no type is specified
- It is now allowed to install Symfony 4

### Removed
- Support for old versions of PHP and Symfony.

## [3.1.7](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.6...3.1.7) - 2017-09-14
### Fixed
- added the missing `sonata-ba-field-error` class to table fields with errors
- Replaced deprecated `getEntityManager` with `getManager`
- Patched collection form handling script to maintain File input state when new items are added to collections
- Fixed invalid FieldDescription for association embedded properties

## [3.1.6](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.5...3.1.6) - 2017-07-05
### Fixed
- Throw an exception if property name is not found in field mappings
- Fixed `ModelManager::getDataSourceIterator` when` getSortBy` is empty
- Wrong DQL generated for many to many relationship when filtering with not equals
- Fixed ClassFilter for Symfony 3+. Remove deprecated for Symfony > 2.7

## [3.1.5](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.4...3.1.5) - 2017-06-16
### Fixed
- A list field with `actions` type will get all the required field options just like the `_action` field.
- `_action` field will get a proper `actions` type.
- `ModelFilter::handleMultiple` fix method to retrieve parent alias for building IDENTITY query part
- One-to-many and many-to-many association script will not try to load links with "javascript:" hrefs via XHR.
- Fixed `AddAuditEntityCompilerPass::process()` when definition `simplethings.entityaudit.audited_entities` is not present, as of `2.x` version for `simplethings/entity-audit-bundle`.

## [3.1.4](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.3...3.1.4) - 2017-03-23
### Fixed
- Fixed broken translation in oneToMany table view
- Fixed wrong translation in delete checkbox in `edit_orm_one_to_many_inline_table.html.twig`

### Security
- Fixed view - check specific item collection, not to the whole collection.

## [3.1.3](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.2...3.1.3) - 2017-01-17
### Fixed
- Consider NULL values when using 'is not equal' advanced model filter
- Use the right array conversion for filter value
- Fixed wrong link generation when calling `DatagridMapper::addIdentifier` on mapped field
- Fixed duplicate translation of "Delete" in edit tab view

### Changed
- Translation in twig templates uses the twig translation filter

## [3.1.2](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.1...3.1.2) - 2016-12-05
### Changed
- ORM any-to-any list and show templates now use `hasAccess`

### Fixed
- Fixed typo in exception message in `FormContractor`

## [3.1.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.1.0...3.1.1) - 2016-10-04
### Fixed
- Use mor reliable `UnitOfWork::getEntityState()` method to detect persisted entities.
- Typo on `RuntimeException` usages

## [3.1.0](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.0.5...3.1.0) - 2016-09-12
### Changed
- Date range filter can now be used with only one side defined
- Use class names for filters when using symfony 2.8+
- Changed how `FormContractor::getDefaultOptions` checks which type is used. Instead of checking for an array of available types, we improve this by checking for the class instance or parents.

### Fixed
- Allow not mapped field to use `admin_code` option for `sonata_type_model_list`
- `FormContractor` supports the new `Sonata\AdminBundle\Form\Type\ModelListType`
- Add missing translation of 'Delete' in edit view
- Use class name when referencing `Form Type` to be compatible with Symfony 2.8+

### Removed
- internal test classes are now excluded from the autoloader

## [3.0.5](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.0.4...3.0.5) - 2016-06-05
### Fixed
- Fix `FormContractor::getDefaultOptions` not checking against form types FQCNs

## [3.0.4](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.0.3...3.0.4) - 2016-06-17
### Fixed
- Fix wrong property name on FormContractor
- Create form is shown instead of filters on `sonata_type_model_list` popup

## [3.0.3](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.0.2...3.0.3) - 2016-06-09
### Fixed
- Failing identifier management for relations as id
- Deprecated usage of `form` type name

## [3.0.2](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.0.1...3.0.2) - 2016-06-03
### Fixed
- Avoid duplicate field in ORDER clause
- Support embedded object for mapping

## [3.0.1](https://github.com/sonata-project/SonataDoctrineORMAdminBundle/compare/3.0.0...3.0.1) - 2016-05-22
### Fixed
- Added missing default sort by primary key(s).
- Allow non integer/string types as identifier (ex. uuid).
