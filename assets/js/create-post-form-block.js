(function (blocks, element) {
    var el = element.createElement;

    var blockStyle = {
        backgroundColor: '#0073aa',
        color: '#fff',
        padding: '20px',
    };

    blocks.registerBlockType('create-post/form', {
        title: 'Create Post Form',
        icon: 'edit',
        category: 'widgets',
        example: {},
        edit: function () {
            return el(
                    'p',
                    {style: blockStyle},
                    'Create Post Form'
                    );
        },
        save: function () {
            return el(
                    'div',
                    {},
                    '[create_post_form]'
                    );
        },
    });
}(window.wp.blocks, window.wp.element));