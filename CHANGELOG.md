# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

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
