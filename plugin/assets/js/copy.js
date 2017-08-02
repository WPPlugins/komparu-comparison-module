if (jQuery)(function ($) {
    $(document).on('click', '.compmodule_table tr td input[type="text"]', function (e) {
        e.preventDefault();
        $(this).select();
    });
    $(document).on('click', '.compmodule_drop_cache', function (e) {
        e.preventDefault();
        var self = this;
        $(self).addClass('working');
        var data = {
            action: 'compmodule_drop_cache',
            args: {
                token: $(this).attr('data-token')
            }
        };
        $.post(ajaxurl, data, function (response) {
            alert('Cache deleted');
            $(self).removeClass('working');
        });
    });
    $(document).on('click', '.delete_files', function(e){
        e.preventDefault();
        var self = this;
        $(self).addClass('working');
        var data = {
            action: 'compmodule_delete_files'
        };
        $.post(ajaxurl, data, function (response) {
            alert('All cached files are deleted');
            $(self).removeClass('working');
        });
    });
})(jQuery);