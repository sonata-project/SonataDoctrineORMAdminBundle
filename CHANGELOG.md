# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

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
