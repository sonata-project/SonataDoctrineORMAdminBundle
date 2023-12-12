.. index::
    double: Reference; Configuration
    single: Options

Configuration
=============

The configuration section is only about the ``SonataDoctrineORMAdminBundle``.
For more information about the global configuration of the ``SonataAdminBundle``, please refer to the dedicated documentation.

Full configuration options
==========================

.. code-block:: yaml

    sonata_doctrine_orm_admin:
        # default value is null, so doctrine uses the value defined in the configuration
        entity_manager: ~

        audit:
            force: true

        templates:
            types:
                list:
                    array:      "@SonataAdmin/CRUD/list_array.html.twig"
                    boolean:    "@SonataAdmin/CRUD/list_boolean.html.twig"
                    date:       "@SonataAdmin/CRUD/list_date.html.twig"
                    time:       "@SonataAdmin/CRUD/list_time.html.twig"
                    datetime:   "@SonataAdmin/CRUD/list_datetime.html.twig"
                    text:       "@SonataAdmin/CRUD/base_list_field.html.twig"
                    trans:      "@SonataAdmin/CRUD/list_trans.html.twig"
                    string:     "@SonataAdmin/CRUD/base_list_field.html.twig"
                    smallint:   "@SonataAdmin/CRUD/base_list_field.html.twig"
                    bigint:     "@SonataAdmin/CRUD/base_list_field.html.twig"
                    integer:    "@SonataAdmin/CRUD/base_list_field.html.twig"
                    decimal:    "@SonataAdmin/CRUD/base_list_field.html.twig"
                    identifier: "@SonataAdmin/CRUD/base_list_field.html.twig"
                    currency:   "@SonataAdmin/CRUD/list_currency.html.twig"
                    percent:    "@SonataAdmin/CRUD/list_percent.html.twig"
                    choice:     "@SonataAdmin/CRUD/list_choice.html.twig"
                    url:        "@SonataAdmin/CRUD/list_url.html.twig"

                show:
                    array:      "@SonataAdmin/CRUD/show_array.html.twig"
                    boolean:    "@SonataAdmin/CRUD/show_boolean.html.twig"
                    date:       "@SonataAdmin/CRUD/show_date.html.twig"
                    time:       "@SonataAdmin/CRUD/show_time.html.twig"
                    datetime:   "@SonataAdmin/CRUD/show_datetime.html.twig"
                    text:       "@SonataAdmin/CRUD/base_show_field.html.twig"
                    trans:      "@SonataAdmin/CRUD/show_trans.html.twig"
                    string:     "@SonataAdmin/CRUD/base_show_field.html.twig"
                    smallint:   "@SonataAdmin/CRUD/base_show_field.html.twig"
                    bigint:     "@SonataAdmin/CRUD/base_show_field.html.twig"
                    integer:    "@SonataAdmin/CRUD/base_show_field.html.twig"
                    decimal:    "@SonataAdmin/CRUD/base_show_field.html.twig"
                    currency:   "@SonataAdmin/CRUD/base_currency.html.twig"
                    percent:    "@SonataAdmin/CRUD/base_percent.html.twig"
                    choice:     "@SonataAdmin/CRUD/show_choice.html.twig"
                    url:        "@SonataAdmin/CRUD/show_url.html.twig"
