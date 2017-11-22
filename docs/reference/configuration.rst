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
            form:
                - SonataDoctrineORMAdminBundle:Form:form_admin_fields.html.twig
            filter:
                - SonataDoctrineORMAdminBundle:Form:filter_admin_fields.html.twig
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
                    currency:   SonataAdminBundle:CRUD:list_currency.html.twig
                    percent:    SonataAdminBundle:CRUD:list_percent.html.twig
                    choice:     SonataAdminBundle:CRUD:list_choice.html.twig
                    url:        SonataAdminBundle:CRUD:list_url.html.twig

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
                    currency:   SonataAdminBundle:CRUD:base_currency.html.twig
                    percent:    SonataAdminBundle:CRUD:base_percent.html.twig
                    choice:     SonataAdminBundle:CRUD:show_choice.html.twig
                    url:        SonataAdminBundle:CRUD:show_url.html.twig
