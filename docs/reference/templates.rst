.. index::
    double: Reference; Templates

Templates
=========

You can customize the global layout by tweaking the ``SonataAdminBundle`` configuration:

.. code-block:: yaml

    sonata_admin:
        templates:
            # default global templates
            layout:  "@SonataAdmin/standard_layout.html.twig"
            ajax:    "@SonataAdmin/ajax_layout.html.twig"

            # default value if done set, actions templates, should extend global templates
            list:    "@SonataAdmin/CRUD/list.html.twig"
            show:    "@SonataAdmin/CRUD/show.html.twig"
            edit:    "@SonataAdmin/CRUD/edit.html.twig"


You can also configure the templates used by the `Form Framework` while rendering the widget:

.. code-block:: yaml

    sonata_doctrine_orm_admin:
        templates:
            form: [ "@SonataDoctrineORMAdmin/Form/form_admin_fields.html.twig" ]
            filter: [ "@SonataDoctrineORMAdmin/Form/filter_admin_fields.html.twig" ]


You can also customize field types by adding types in the ``config.yml`` file. The default values are:

.. code-block:: yaml

    sonata_doctrine_orm_admin:
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

.. note::

    By default, if the ``SonataIntlBundle`` classes are available, then the numeric and date fields will be localized with the current user locale.
