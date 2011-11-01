Templates
=========

You can customize the global layout by tweaking the ``SonataAdminBundle`` configuration.

.. code-block:: yaml

    sonata_admin:
        templates:
            # default global templates
            layout:  SonataAdminBundle::standard_layout.html.twig
            ajax:    SonataAdminBundle::ajax_layout.html.twig

            # default value if done set, actions templates, should extends a global templates
            list:    SonataAdminBundle:CRUD:list.html.twig
            show:    SonataAdminBundle:CRUD:show.html.twig
            edit:    SonataAdminBundle:CRUD:edit.html.twig


You can also configure the templates used by the Form Framework while rendering the widget

.. code-block:: yaml

    sonata_doctrine_orm_admin:
        templates:
            form: [ SonataDoctrineORMAdminBundle:Form:form_admin_fields.html.twig ]
            filter: [ SonataDoctrineORMAdminBundle:Form:filter_admin_fields.html.twig ]
