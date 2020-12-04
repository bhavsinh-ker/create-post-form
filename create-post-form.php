<?php
/*
 * Plugin Name: Create Post Form
 * Plugin URI: https://github.com/bhavsinh-ker/create-post-form
 * Description: This plugin will allow admin to add create post form in front-end.
 * Version: 1.1.0
 * Author: Bhavik Ker
 * Author URI: https://www.linkedin.com/in/bhavik-ker-b8b04786/
 * Text Domain: create-post-form
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('ABSPATH') || exit;

include( plugin_dir_path(__FILE__) . 'inc/CreatePostFormCore.class.php');

class CreatePostForm extends CreatePostFormCore {

    public function __construct() {
        parent::__construct();
        add_action('admin_init', array($this, 'register_create_post_form_gutenberg_block'));
        add_action('init', array($this, 'create_post_form_script_enqueuer'));
        add_shortcode('create_post_form', array($this, 'create_post_form_shortcode_function'));
    }

    /**
     * Register create post form gutenberg block
     *
     * This function will enqueue the necessary Java Scripts for create post form gutenberg block.
     *
     * @see wp_register_script(), register_block_type() and function_exists()
     */
    public function register_create_post_form_gutenberg_block() {
        if (!function_exists('register_block_type')) {
            // Gutenberg is not active.
            return;
        }

        wp_register_script(
                'create-post-form-block', plugins_url('assets/js/create-post-form-block.js', __FILE__), array('wp-blocks', 'wp-i18n', 'wp-element'), filemtime(plugin_dir_path(__FILE__) . 'assets/js/create-post-form-block.js')
        );

        register_block_type('create-post/form', array(
            'editor_script' => 'create-post-form-block',
        ));
    }

    /**
     * Create post script enqueuer.
     *
     * This function will enqueue the necessary Java Scripts for create post shortcode.
     *
     * @see wp_register_script(), wp_localize_script() and wp_enqueue_script()
     */
    public function create_post_form_script_enqueuer() {
        wp_register_script('create-post-form-script', plugins_url('assets/js/create-post-form.js', __FILE__), array('jquery'));
        wp_localize_script('create-post-form-script', 'createPostFormConfig', array('ajaxUrl' => admin_url('admin-ajax.php')));
        wp_enqueue_script('jquery');
        wp_enqueue_script('create-post-form-script');

        wp_register_style('create-post-form-styles', plugins_url('assets/css/create-post-form-styles.css', __FILE__));
        wp_enqueue_style('create-post-form-styles');
    }

    /**
     * Create post form function.
     *
     * Call back function for [create_post_form] shortcode.
     *
     * @see author_login_check(), get_post_types_list(), wp_editor() and wp_login_form()
     *
     * @return html html of create post form or login form.
     */
    public function create_post_form_shortcode_function($atts, $content = "") {
        ob_start();
        if ($this->author_login_check()) {
            ?>
            <form name="create-post-form-element" id="createPost" onsubmit="saveMyPost(this); return false;" method="post" enctype="multipart/form-data">
                <h5><?php _e('Create Post', 'create-post-form'); ?></h5>

                <input type="hidden" name="action" id="action" value="create_post_ajax" />

                <div class="alart"></div>

                <p class="createpost-post-title">
                    <label for="post_title"><?php _e('Post Title', 'create-post-form'); ?></label>
                    <input type="text" name="post_title" id="post_title" class="input" required="required" />
                </p>

                <?php
                $post_types = $this->get_post_types_list();
                if (!empty($post_types)) {
                    ?>
                    <p class="createpost-post-type">
                        <label for="post_type"><?php _e('Post Types', 'create-post-form'); ?></label>
                        <select name="post_type" id="post_type" class="input">
                            <?php foreach ($post_types as $value => $label) { ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php } ?>
                        </select>
                    </p>
                <?php } else {
                    ?>
                    <input type="hidden" value="post" name="post_type" id="post_type" />
                    <?php
                }
                ?>

                <p class="createpost-description">
                    <label for="description"><?php _e('Description', 'create-post-form'); ?></label>
                    <?php
                    $editor_content = '';
                    $editor_id = 'description';
                    $editor_settings = array(
                        'name' => 'description',
                        'editor_height' => 350,
                        'teeny' => true
                    );
                    wp_editor($editor_content, $editor_id, $editor_settings);
                    ?>
                </p>

                <p class="createpost-excerpt">
                    <label for="excerpt"><?php _e('Excerpt', 'create-post-form'); ?></label>
                    <textarea name="excerpt" id="excerpt" class="input"></textarea>
                </p>

                <p class="createpost-featured-image">
                    <label for="featured_image"><?php _e('Featured image', 'create-post-form'); ?></label>
                    <input type="file" name="featured_image" id="featured_image" class="input" accept="image/x-png,image/gif,image/jpeg" />
                </p>

                <p class="createpost-submit">
                    <button type="submit" id="save_post" class="button button-primary"><?php _e('Save Post', 'create-post-form'); ?></button>
                    <span class="loader dashicons dashicons-update spin" style="display: none;"></span>
                </p>

            </form>
            <?php
        } else {
            ?>
            <h5><?php _e('Login', 'create-post-form'); ?></h5>
            <?php
            $args = array(
                'echo' => true,
                'redirect' => get_permalink(get_the_ID()),
                'remember' => true,
                'value_remember' => true
            );

            wp_login_form($args);
        }
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

}

$createPostForm = new CreatePostForm();
