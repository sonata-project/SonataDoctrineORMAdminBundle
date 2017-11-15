.. index::
    double: Reference; Templates

Templates
=========

You can customize the global layout by tweaking the ``SonataAdminBundle`` configuration:

.. code-block:: yaml

    sonata_admin:
        templates:
            # default global templates
            layout:  SonataAdminBundle::standard_layout.html.twig
            ajax:    SonataAdminBundle::ajax_layout.html.twig

            # default value if done set, actions templates, should extend global templates
            list:    SonataAdminBundle:CRUD:list.html.twig
            show:    SonataAdminBundle:CRUD:show.html.twig
            edit:    SonataAdminBundle:CRUD:edit.html.twig


You can also configure the templates used by the `Form Framework` while rendering the widget:

.. code-block:: yaml

    sonata_doctrine_orm_admin:
        templates:
            form: [ SonataDoctrineORMAdminBundle:Form:form_admin_fields.html.twig ]
            filter: [ SonataDoctrineORMAdminBundle:Form:filter_admin_fields.html.twig ]


You can also customize field types by adding types in the ``config.yml`` file. The default values are:

.. code-block:: yaml

    sonata_doctrine_orm_admin:
        templates:
            types:
                list:
                    array:      SonataAdminBundle:CRUD:list_array.html.twig
                    boolean:    SonataAdminBundle:CRUD:list_boolean.html.twig
                    date:       SonataAdminBundle:CRUD:list_date.html.twig
                    time:       SonataAdminBundle:CRUD:list_time.html.twig
                    datetime:   SonataAdminBundle:CRUD:list_datetime.html.twig
                    text:       SonataAdminBundle:CRUD:base_list_field.html.twig
                    trans:      SonataAdminBundle:CRUD:list_trans.html.twig
                    string:     SonataAdminBundle:CRUD:base_list_field.html.twig
                    smallint:   SonataAdminBundle:CRUD:base_list_field.html.twig
                    bigint:     SonataAdminBundle:CRUD:base_list_field.html.twig
                    integer:    SonataAdminBundle:CRUD:base_list_field.html.twig
                    decimal:    SonataAdminBundle:CRUD:base_list_field.html.twig
                    identifier: SonataAdminBundle:CRUD:base_list_field.html.twig

                show:
                    array:      SonataAdminBundle:CRUD:show_array.html.twig
                    boolean:    SonataAdminBundle:CRUD:show_boolean.html.twig
                    date:       SonataAdminBundle:CRUD:show_date.html.twig
                    time:       SonataAdminBundle:CRUD:show_time.html.twig
                    datetime:   SonataAdminBundle:CRUD:show_datetime.html.twig
                    text:       SonataAdminBundle:CRUD:base_show_field.html.twig
                    trans:      SonataAdminBundle:CRUD:show_trans.html.twig
                    string:     SonataAdminBundle:CRUD:base_show_field.html.twig
                    smallint:   SonataAdminBundle:CRUD:base_show_field.html.twig
                    bigint:     SonataAdminBundle:CRUD:base_show_field.html.twig
                    integer:    SonataAdminBundle:CRUD:base_show_field.html.twig
                    decimal:    SonataAdminBundle:CRUD:base_show_field.html.twig

.. note::

    By default, if the ``SonataIntlBundle`` classes are available, then the numeric and date fields will be localized with the current user locale.