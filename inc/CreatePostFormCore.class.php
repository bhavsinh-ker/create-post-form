<?php

class CreatePostFormCore {
    
    public function __construct() {
        add_action('wp_ajax_create_post_ajax', array($this, 'create_post_ajax'));
    }

    /**
     * Author login check.
     *
     * This function can check current user is login or not and also check current user is author or not.
     *
     * @see wp_get_current_user()
     * @return boolean TRUE/FALSE.
     */
    protected function author_login_check() {
        $current_user = wp_get_current_user();
        $is_author_user = FALSE;
        if (is_wp_error($current_user)) {
            return $is_author_user;
        }
        if (isset($current_user->roles) && (in_array('author', $current_user->roles) || in_array('administrator', $current_user->roles))) {
            $is_author_user = TRUE;
        }
        return $is_author_user;
    }

    /**
     * Get post types list.
     *
     * This function can retrieve all public post types.
     *
     * @see get_post_types()
     *
     * @return array list of public post types in array.
     */
    protected function get_post_types_list() {
        $args = array(
            'public' => true
        );
        $post_types = get_post_types($args, 'objects');
        $post_type_list = array();
        if (!is_wp_error($post_types) && !empty($post_types)) {
            $excluded_post_type = array(
                'attachment'
            );
            foreach ($post_types as $post_type) {
                if (!in_array($post_type->name, $excluded_post_type)) {
                    $post_type_list[$post_type->name] = $post_type->labels->singular_name;
                }
            }
        }
        return $post_type_list;
    }

    /**
     * Upload my featured image.
     *
     * This function will help to upload image in WordPress media and set featured image of post.
     *
     * @see wp_handle_upload(), wp_check_filetype(), wp_insert_attachment(), wp_generate_attachment_metadata(), wp_update_attachment_metadata() and set_post_thumbnail()
     *
     * @param input file $featured_image image file which want to upload.
     * @param int $parent_post_id Optional. post id which need to set for featured image. Default 0.
     * @return boolean TRUE/FALSE.
     */
    protected function upload_my_featured_image($featured_image, $parent_post_id = 0) {

        if (!function_exists('wp_handle_upload')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        $uploadedfile = $featured_image;
        $upload_overrides = array(
            'test_form' => false
        );
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if (isset($movefile['error']) && !$movefile) {
            return FALSE;
        }

        // $file_url should be the path to a file in the upload directory.
        $file_url = $movefile['url'];

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($file_url), null);

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid' => $movefile['url'],
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_url)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert the attachment.
        if ($parent_post_id !== 0) {
            $attach_id = wp_insert_attachment($attachment, $file_url, $parent_post_id);
        } else {
            $attach_id = wp_insert_attachment($attachment, $file_url);
        }

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_url);
        wp_update_attachment_metadata($attach_id, $attach_data);
        if ($parent_post_id !== 0) {
            set_post_thumbnail($parent_post_id, $attach_id);
        }
        return $attach_id;
    }

    /**
     * Create post ajax function.
     *
     * This function will execute when Create post ajax will fire. This function will create new post and send email notification to admin user.
     *
     * @see wp_get_current_user(), esc_js(), sanitize_text_field(), sanitize_textarea_field(), wp_strip_all_tags(), wp_insert_post(), upload_my_featured_image(), get_bloginfo(), get_edit_post_link(), wp_mail()
     *
     * @return json status of ajax call in json object.
     */
    public function create_post_ajax() {

        $return = array(
            'status' => FALSE,
            'message' => ''
        );

        /* validation process */
        $title = (isset($_REQUEST['post_title']) && $_REQUEST['post_title'] != "") ? esc_js(sanitize_text_field($_REQUEST['post_title'])) : FALSE;
        if (!$title) {
            $return['message'] = 'validation error';
            $return['data'] = array(
                'field_name' => 'post_title',
                'message' => 'Post Title is required'
            );
            die(json_encode($return));
        }
        /* EOF validation process */

        $post_type = (isset($_REQUEST['post_type']) && $_REQUEST['post_type'] != "") ? esc_js(sanitize_text_field($_REQUEST['post_type'])) : 'post';
        $description = (isset($_REQUEST['description']) && $_REQUEST['description'] != "") ? esc_js(sanitize_textarea_field($_REQUEST['description'])) : '';
        if ($post_type == "page" || $post_type == "post") {
            $description = '<!-- wp:paragraph -->' . $description . '<!-- /wp:paragraph -->';
        }
        $excerpt = (isset($_REQUEST['excerpt']) && $_REQUEST['excerpt'] != "") ? esc_js(sanitize_textarea_field($_REQUEST['excerpt'])) : '';
        $featured_image = (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) ? $_FILES['featured_image'] : FALSE;
        $current_user = wp_get_current_user();
        $post_author_id = $current_user->ID;

        /* Insert post process */
        $post_data = array(
            'post_title' => wp_strip_all_tags($title),
            'post_content' => $description,
            'post_excerpt' => $excerpt,
            'post_type' => $post_type,
            'post_status' => 'draft',
            'post_author' => $post_author_id
        );

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            $return['message'] = $post_id->get_error_message();
            die(json_encode($return));
        }
        /* EOF Insert post process */

        /* Image Upload process */
        if ($featured_image) {
            $attachment_id = $this->upload_my_featured_image($featured_image, $post_id);
            if (!$attachment_id) {
                $return['message'] = 'Post is inserted but featured image is not uploaded';
                die(json_encode($return));
            }
            $return['data']['attachment_id'] = $attachment_id;
        }
        /* EOF Image Upload process */

        /* Send admin email */
        $website_name = get_bloginfo('name');
        $admin_email = get_bloginfo('admin_email');
        $subject = 'New post has been created in ' . $website_name;
        $body = 'Hello Admin, <br /> New post has been created by <b>' . $current_user->display_name . '</b> author in your ' . $website_name . ' website. <br /> You can review it by this link ' . get_edit_post_link($post_id);
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $website_name . ' <' . $admin_email . '>'
        );
        wp_mail($admin_email, $subject, $body, $headers);
        /* EOF Send admin email */

        $return['status'] = TRUE;
        $return['message'] = 'Post created successfully';
        $return['data']['post_id'] = $post_id;
        die(json_encode($return));
    }

}
