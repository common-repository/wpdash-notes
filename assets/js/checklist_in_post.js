(function () {
    tinymce.create("tinymce.plugins.green_button_plugin", {
        //url argument holds the absolute url of our plugin directory
        init: function (ed, url) {

            //add new button
            ed.addButton("green", {
                title: "Add Checklist",
                text: "Add Checklist in Post",
                cmd: "green_command",
                icon: "icon-check"
            });

            //button functionality.
            ed.addCommand("green_command", function () {
                var selected_text = ed.selection.getContent();
                var return_text = "[checklist_in_post]" + selected_text + "[/checklist_in_post]";
                ed.execCommand("mceInsertContent", false, return_text);
            });

        },

        createControl: function (n, cm) {
            return null;
        },

        getInfo: function () {
            return {
                longname: "Add Checklist Button",
                author: "Tomasz Małecki",
                version: "1"
            };
        }
    });

    tinymce.PluginManager.add("green_button_plugin", tinymce.plugins.green_button_plugin);
})();