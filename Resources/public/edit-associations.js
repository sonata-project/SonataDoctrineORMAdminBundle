(function ($, Admin) {
    "use strict";
    //
    // Type definitions
    //-----------------------------------------------------------------------------------------------------------------

    /**
     * Field description passed by the Sonata admin templates.
     *
     * @typedef {{
     *   id: string,
     *   ?label: string,
     *   ?editMode: string,
     *   ?formType: string,
     *   routes: {
     *      ?shortObjectDescription: string,
     *      ?retrieveFormElement: string,
     *      ?appendFormElement: string,
     *   }
     * }} FieldDescriptionType
     */

    /**
     * @typedef {{
     *   dialog: jQuery,
     *   title: jQuery,
     *   body: jQuery,
     *   spinner: jQuery
     * }} DialogType
     */

    /**
     * @typedef {{
     *   fieldDescription: FieldDescriptionType,
     *   dialog: ?DialogType
     * }} FieldActionType
     */

    //
    // Utilities
    //-----------------------------------------------------------------------------------------------------------------

    /**
     * Returns the form field with the given id.
     *
     * @param {string} fieldId
     * @returns {jQuery}
     */
    function getField (fieldId) {
        return $('#' + fieldId);
    }
    /**
     * Returns the field container of the form field dentified by the given id.
     *
     * @param {string} fieldId
     * @returns {jQuery}
     */
    function getFieldContainer (fieldId) {
        return $('#field_container_' + fieldId);
    }
    /**
     * Returns the field widget of the form field dentified by the given id.
     *
     * @param {string} fieldId
     * @returns {jQuery}
     */
    function getFieldWidget (fieldId) {
        return $('#field_widget_' + fieldId);
    }
    /**
     * Returns the parent list field (cell) of the given element,
     * possibly limited to the given contextNode.
     *
     * @param {string} fieldId
     * @returns {jQuery}
     */
    function getParentListField (element, contextNode) {
        return $(element).closest('.sonata-ba-list-field', contextNode || document);
    }

    function logEvent (id, stack, message) {
        Admin.log('[' + id + '|' + stack + '] ' + message);
    }

    function logFieldAction (action, stack, message) {
        logEvent(action.fieldDescription.id, stack, message);
    }

    /**
     * Returns whether the given element is an anchor inside the same page.
     *
     * @param {jQuery} $el
     * @returns {boolean}
     */
    function isAnchor ($el) {
        var href = $el.attr('href');
        return $el.is('a') && (!href || href[0] === '#');
    }

    /**
     * Retrieves the form field description from a DOM Element.
     * The field description must be passed to the element as a serialized JSON object,
     * via a [data-field-description] attribute.
     * The JSON object must conform to the {FieldDescriptionType} definition.
     *
     * @param {jQuery} $el
     * @returns {FieldDescriptionType}
     */
    function getFieldDescription ($element) {
        return $element.data('fieldDescription');
    }

    // ==================== Former edit_many_association_script.html.twig ==================== //

    //
    // Dialog
    //-----------------------------------------------------------------------------------------------------------------

    //TODO: role="status" requires status text
    var DIALOG_TEMPLATE =
        '<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">' +
            '<div class="modal-dialog modal-lg">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
                        '<h4 class="modal-title"></h4>' +
                    '</div>' +
                    '<div class="modal-body"></div>' +
                    '<div class="overlay spinner-overlay text-primary"></div>' +
                '</div>' +
            '</div>' +
        '</div>'
    ;

    /**
     * Creates the modal dialog DOM.
     *
     * @param {string} id The id of the modal dialog.
     * @param {string} title
     *
     * @returns {DialogType}
     */
    function createDialog (id, title) {
        var $modal = $(DIALOG_TEMPLATE).attr('id', 'field_dialog_' + id);
        return {
            root: $modal.attr('aria-labelledby', 'field_dialog_title_' + id),
            title: $modal.find('.modal-title').attr('id', 'field_dialog_title_' + id).text(title),
            body: $modal.find('.modal-body'),
            //TODO: {{ 'loading_information'|trans({}, 'SonataAdminBundle') }}
            spinner: $modal.find('.overlay').append(Admin.createSpinner(64, 'Loading...'))
        };
    }

    /**
     * Returns a new modal dialog from a FieldDescriptionType,
     * and injects it into the document.
     *
     * @param {FieldDescriptionType} fieldDescription
     * @returns {DialogType}
     */
    function createActionDialog (fieldDescription) {
        var dialog = createDialog(fieldDescription.id, fieldDescription.label);
        $(document.body).append(dialog.root);
        logEvent(fieldDescription.id, 'createActionDialog', 'insert dialog to the DOM.');
        return dialog;
    }

    /**
     * Returns a FieldActionType object.
     *
     * @param {FieldDescriptionType} fieldDescription
     * @param {?DialogType} dialog
     * @returns {FieldActionType}
     */
    function createFieldAction (fieldDescription, dialog) {
        return {
            fieldDescription: fieldDescription,
            dialog: dialog
        };
    }

    /**
     * @param {FieldActionType} action
     * @param {Function} onShow
     */
    function showActionDialog (action, onShow) {
        Admin.setup_list_modal(action.dialog.root);
        action.dialog.root.one('shown.bs.modal', onShow).modal();
    }
    /**
     * @param {FieldActionType} action
     * @param {Function} onHide
     */
    function closeActionDialog (action, onHide) {
        action.dialog.root.one('hidden.bs.modal', function () {
            action.dialog.root.remove();
            onHide && onHide();
        }).modal('hide');
    }
    /**
     * @param {FieldActionType} action
     * @param {string} html
     */
    function populateActionDialog (action, html) {
        action.dialog.body.html(html);
        Admin.shared_setup(action.dialog.body);
    }
    /**
     * @param {FieldActionType} action
     */
    function showDialogSpinner (action) {
        action.dialog.spinner.fadeIn('fast');
    }
    /**
     * @param {FieldActionType} action
     */
    function hideDialogSpinner (action) {
        action.dialog.spinner.fadeOut('fast');
    }

    /**
     * Called when a request triggered by a field action fails.
     *
     * @param {FieldActionType} action
     * @param {jqXHR} xhr
     */
    function handleActionRequestError (action, xhr) {
        populateActionDialog(action, xhr.responseText);
        hideDialogSpinner(action);
    }

    /**
     * Fetches the related object short description.
     *
     * @param {FieldActionType} action
     * @param {string} objectId
     */
    function updateShortObjectDescription (action, objectId) {
        // update the label
        logFieldAction(action, 'onchange', 'update field label');
        var fieldDescription = action.fieldDescription;
        getField(fieldDescription.id).val(objectId);
        getFieldWidget(fieldDescription.id).addClass('loading').empty().append(Admin.createSpinner());
        $.ajax({
            url: fieldDescription.routes.shortObjectDescription.replace('OBJECT_ID', objectId),
            dataType: 'html'
        }).done(function (html) {
            getFieldWidget(fieldDescription.id).removeClass('loading').html(html);
        });
    }

    /**
     * handle link click in a list :
     *  - if the parent has an objectId defined then the related input gets updated
     *  - if the parent has NO objectId then an ajax request is made to refresh the popup
     *
     * @param event
     */
    function handleListDialogClick (event) {
        var $link = $(this);
        var action = event.data;
        if (isAnchor($link)) {
            logFieldAction(action, 'handleListDialogClick', 'element is an anchor, skipping action');
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        logFieldAction(action, 'handleListDialogClick', 'handle link click in a list');
        var $parentListField = getParentListField($link, action.dialog.root);
        if (!$parentListField.length) {
            // the user does not click on a row column
            logFieldAction(action, 'handleListDialogClick', 'the user does not click on a row column, make ajax call to retrieve inner html');
            // make a recursive call (ie: reset the filter)
            showDialogSpinner(action);
            $.ajax({
                url: $link.attr('href'),
                dataType: 'html'
            }).done(function (html) {
                logFieldAction(action, 'handleListDialogClick', 'callback success, attach valid js event');
                hideDialogSpinner(action);
                populateActionDialog(action, html);
            }).fail(handleActionRequestError.bind(null, action));

            return;
        }

        logFieldAction(action, 'handleListDialogClick', 'the user select one element, update input and hide the modal');
        updateShortObjectDescription(action, $parentListField.attr('objectId'));
        closeActionDialog(action);
    }

    /**
     * Handle form submissions in list modal.
     * Capture the submit event to make an ajax call, ie : POST data to the related create admin.
     *
     * @param event
     */
    function handleListDialogSubmit (event) {
        event.preventDefault();
        var action = event.data;
        var $form = $(this);
        logFieldAction(action, 'handleListDialogSubmit', 'catching submit event, sending ajax request');
        showDialogSpinner(action);
        $form.ajaxSubmit({
            method: $form.attr('method'),
            url: $form.attr('action'),
            dataType: 'html',
            data: {_xml_http_request: true},
            success: function (html) {
                logFieldAction(action, 'handleListDialogSubmit', 'form submit success, restoring event');
                hideDialogSpinner(action);
                populateActionDialog(action, html);
            },
            error: handleActionRequestError.bind(null, action)
        });
    }

    /**
     * Handle form submissions & click events in the "create" modal.
     *
     * @param event
     */
    function handleCreateDialogSubmit (event) {
        var $target = $(this);
        var action = event.data;
        if (isAnchor($target)) {
            // a click on a tab or similar.
            logFieldAction(action, 'handleCreateDialogSubmit', 'element is an anchor, skipping action');
            return;
        }
        event.preventDefault();
        if ($target.hasClass('sonata-ba-action')) {
            logFieldAction(action, 'handleCreateDialogSubmit', 'reserved action stop catch all events');
            return;
        }

        var method = 'get', url;
        if ($target.is('form')) {
            url = $target.attr('action');
            method = $target.attr('method');
        } else if ($target.is('a')) {
            // happens in the create action of sonata media admin
            url = $target.attr('href');
        }
        showDialogSpinner(action);
        $target.ajaxSubmit({
            url: url,
            method: method,
            data: {_xml_http_request: true},
            success: function (data) {
                logFieldAction(action, 'handleCreateDialogClick', 'ajax success');
                hideDialogSpinner(action);
                // if the crud action return ok, then the element has been added
                // so the widget container must be refresh with the last option available
                if (data.result !== 'ok') {
                    populateActionDialog(action, data);
                    return;
                }
                closeActionDialog(action, function () {
                    var fieldDescription = action.fieldDescription;
                    if (fieldDescription.editMode === 'list') {
                        // in this case we update the hidden input,
                        // and call the change event to retrieve the post information
                        updateShortObjectDescription(action, data.objectId);
                        return;
                    }
                    getFieldWidget(fieldDescription.id).closest('form').ajaxSubmit({
                        method: 'post',
                        url: fieldDescription.routes.retrieveFormElement,
                        data: {_xml_http_request: true},
                        dataType: 'html',
                        success: function (html) {
                            getFieldContainer(fieldDescription.id).replaceWith(html);
                            var $newElement = getField(fieldDescription.id).find('[value="' + data.objectId + '"]');
                            if ($newElement.is('input')) {
                                $newElement.attr('checked', 'checked');
                            } else {
                                $newElement.attr('selected', 'selected');
                            }

                            getFieldContainer(fieldDescription.id).trigger('sonata-admin-append-form-element');
                        }
                    });
                });
            },
            error: handleActionRequestError.bind(null, action)
        });
    }

    /**
     * Shows the dialog to choose an association from a list view.
     *
     * @param {string} url
     * @param {FieldActionType} action
     */
    function showListDialog (url, action) {
        logFieldAction(action, 'showListDialog', 'open the list modal');
        showActionDialog(action, function () {
            $.ajax({
                url: url,
                dataType: 'html',
            }).done(function (html) {
                logFieldAction(action, 'showListDialog', 'retrieved the list content');
                populateActionDialog(action, html);
                // setup event listeners on the modal, passing our action
                // note that jQuery submit events DO bubble
                action.dialog.root
                    .on('click', 'a', action, handleListDialogClick)
                    .on('submit', 'form', action, handleListDialogSubmit);
                hideDialogSpinner(action);
            }).fail(handleActionRequestError.bind(null, action));
        });
    }

    /**
     * Shows the dialog to create a new association.
     *
     * @param {string} url
     * @param {FieldActionType} action
     */
    function showCreateDialog (url, action) {
        logFieldAction(action, 'showCreateDialog', 'open the create modal');
        showActionDialog(action, function () {
            $.ajax({
                url: url,
                dataType: 'html'
            }).done(function (html) {
                logFieldAction(action, 'showCreateDialog', 'retrieving the create form');
                populateActionDialog(action, html);
                // setup event listeners on the modal, passing our action
                // note that jQuery submit events DO bubble
                action.dialog.root
                    .on('click', 'a', action, handleCreateDialogSubmit)
                    .on('submit', 'form', action, handleCreateDialogSubmit);
                hideDialogSpinner(action);
            }).fail(handleActionRequestError.bind(null, action));
        });
    }

    //
    // Bootstrap listeners
    // ---------------------------------------------------------------------------------------------------------------

    // List button
    $(document).on('click', '.sonata-ba-action[data-field-action="list-association"]', function (event) {
        event.preventDefault();
        var $link = $(this);
        var fieldDescription = getFieldDescription($link);
        var dialog = createActionDialog(fieldDescription);
        showListDialog($link.attr('href'), createFieldAction(fieldDescription, dialog));
    });

    // Create button
    $(document).on('click', '.sonata-ba-action[data-field-action="create-association"]', function (event) {
        event.preventDefault();
        var $link = $(this);
        var fieldDescription = getFieldDescription($link);
        var dialog = createActionDialog(fieldDescription);
        showCreateDialog($link.attr('href'), createFieldAction(fieldDescription, dialog));
    });

    // Delete button
    $(document).on('click', '.sonata-ba-action[data-field-action="remove-association"]', function (event) {
        event.preventDefault();
        var $link = $(this);
        var fieldDescription = getFieldDescription($link);
        var $field = getField(fieldDescription.id);
        if (!$field.val()) {
            return;
        }
        // if field is a select input, unselect all
        if ($field.find('option').get(0)) {
            $field.attr('selectedIndex', '-1').children("option:selected").attr("selected", false);
        }
        updateShortObjectDescription(createFieldAction(fieldDescription), '');
    });


    // ==================== Former edit_one_association_script.html.twig ==================== //

    /**
     * Appends a new association field to a sonata_type_collection
     *
     * @param {FieldDescriptionType} fieldDescription
     */
    function appendFormElement (fieldDescription) {
        var $fieldContainer = getFieldContainer(fieldDescription.id);
        var $form = $fieldContainer.closest('form');
        var $spinner = $('<div class="overlay spinner-overlay text-primary"/>')
            .append(Admin.createSpinner(32))
            .appendTo($fieldContainer.closest('.box'));
        $form.ajaxSubmit({
            url: fieldDescription.routes.appendFormElement,
            method: 'post',
            dataType: 'html',
            data: {_xml_http_request: true},
            success: function (html, statusText, xhr) {
                getFieldContainer(fieldDescription.id).replaceWith(html);
                $spinner.remove();
                var $newContainer = getFieldContainer(fieldDescription.id);
                Admin.shared_setup($newContainer);
                if($('input[type="file"]', $form).length > 0) {
                    $form.attr('enctype', 'multipart/form-data');
                    $form.attr('encoding', 'multipart/form-data');
                }
                $('#sonata-ba-field-container-' + fieldDescription.id).trigger('sonata.add_element');
                $newContainer.trigger('sonata.add_element');
            },
            error: function (xhr, statusText) {
                $spinner.empty().css({cursor: 'pointer'}).one('click', function () {
                    $spinner.remove();
                });
                $('<div class="alert alert-danger alert-dismissible"/>')
                    .append('<button class="close" aria-label="Close"><i class="fa fa-close"/></button>')
                    .append($('<strong/>').text(xhr.statusText))
                    .appendTo($spinner);
            }
        });
    }

    // Used in: edit_orm_one_to_many, edit_orm_many_to_many
    $(document).on('click', '.sonata-ba-action[data-field-action="append-form-element"]', function (event) {
        event.preventDefault();
        var fieldDescription = getFieldDescription($(this));
        appendFormElement(fieldDescription);
    });

    // ==================== Sortable associations ==================== //

    $(function () {
        function addDragHandles ($sortable) {
            $sortable.find('.sonata-ba-sortable-handler')
                .append('<i class="fa fa-bars"/>');
        }
        function applyPositions ($sortable) {
            $sortable.find('.sonata-ba-sortable-position').each(function (index, el) {
                $(el).find('input').val(index + 1);
            });
        }

        function setupSortables (elements) {
            return $(elements).sortable({
                axis: 'y',
                opacity: 0.6,
                cursor: 'grabbing',
                handle: '.sonata-ba-sortable-handler',
                create: function (event) {
                    var $sortable = $(event.target);
                    addDragHandles($sortable);
                    applyPositions($sortable);
                },
                stop: function (event, ui) {
                    applyPositions($(event.target));
                }
            });
        }

        setupSortables('.sonata-ba-sortable');
        $(document).on('sonata.add_element', function (event) {
            setupSortables($(event.target).find('.sonata-ba-sortable'));
        });
    });

}(jQuery, Admin));