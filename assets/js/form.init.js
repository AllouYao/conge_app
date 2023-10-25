!function ($) {
    "use strict";
    var Components = function () {
    };


    //initializing Custom Select
    Components.prototype.initCustomSelect = function () {
        $('[data-plugin="customselect"]').select2();
    },

        //initializing form validation
        Components.prototype.initMultiSelect = function () {
            if ($('[data-plugin="multiselect"]').length > 0)
                $('[data-plugin="multiselect"]').multiSelect($(this).data());
        },



        //initilizing
        Components.prototype.init = function () {
            var $this = this;
            this.initCustomSelect();
        },

        $.Components = new Components, $.Components.Constructor = Components

}(window.jQuery),
    //initializing main application module
    function ($) {
        "use strict";
        $.Components.init();
    }(window.jQuery);