(function ($) {
    "use strict";

    // see styles.css
    var SPINNER_SPRITE =
        '<svg style="display:none;">' +
            '<symbol id="sonata-spinner" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">' +
                '<g transform="translate(20 50)">' +
                    '<rect x="-10" y="-30" width="20" height="60" />' +
                '</g>' +
                '<g transform="translate(50 50)">' +
                    '<rect x="-10" y="-30" width="20" height="60" />' +
                '</g>' +
                '<g transform="translate(80 50)">' +
                    '<rect x="-10" y="-30" width="20" height="60" />' +
                '</g>' +
            '</symbol>' +
        '</svg>'
    ;
    $(function () {
        $(SPINNER_SPRITE).prependTo(document.body);
    });

    var SPINNER_ICON_TEMPLATE =
        '<svg class="sonata-icon-spinner">' +
            '<use xlink:href="#sonata-spinner"></use>' +
        '</svg>'
    ;

    Admin.createSpinnerIcon = function () {
        return $(SPINNER_ICON_TEMPLATE);
    };

    /**
     * Returns a spinner element with given size and given status text.
     * TODO: {{ "loading_information"|trans([], "SonataAdminBundle") }}
     *
     * @param {Number|String} size
     * @param {?string} status
     * @returns {jQuery}
     */
    Admin.createSpinner = function createSpinner (size, status) {
        var $icon = $(SPINNER_ICON_TEMPLATE).addClass('sonata-spinner__icon').css({width: size, height: size});
        var $status = $('<span class="sonata-spinner__status sr-only" role="status" />').text(status || 'Loading...');
        return $('<span class="sonata-spinner" />').append($icon).append($status);
    };

}(jQuery, Admin));