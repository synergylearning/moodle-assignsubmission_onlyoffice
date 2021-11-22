define(['jquery', 'core/str'], function ($, str) {
    // OnlyOffice editor element.
    const $onlyOfficeEditor = $('#onlyoffice-editor');

    // The config we're sending to OnlyOffice.
    let CONFIG = '';

    /**
     * Display an error message
     * @param error
     */
    const displayError = function(error) {
        const errorIsAvailable = str.get_string(error, 'onlyoffice');

        $.when(errorIsAvailable).done(function(localizedStr) {
            $onlyOfficeEditor.text = localizedStr;
            $onlyOfficeEditor.text(localizedStr).addClass("error");
        });
    };

    /**
     * What to do when OnlyOffice has loaded.
     */
    const loadOpenOffice = function() {
        // DocsAPI must be defined at this point.
        if (typeof DocsAPI === "undefined") {
            displayError('docserverunreachable');
            return;
        }

        // Create our OnlyOffice editor.
        new DocsAPI.DocEditor("onlyoffice-editor-submission", CONFIG);
    };

    return {
        init: function () {
            var $configEl = $('input[name="config"]');
            let config = $configEl.val();
            CONFIG = JSON.parse(config);

            // Keep trying to use DocsAPI (might not be loaded).
            window.setInterval(function() {
                if (typeof DocsAPI !== "undefined") {
                    loadOpenOffice();
                    clearInterval();
                }
            }, 5000);
        }
    };
});