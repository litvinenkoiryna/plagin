<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Email_Admin_Product_Notification extends WC_Email {

    public function __construct() {
        $this->id          = 'admin_product_notification';
        $this->title       = __( 'Admin Product Notification', 'wp-my-product-webspark' );
        $this->description = __( 'Notification sent to the admin when a product is created or edited.', 'wp-my-product-webspark' );

        $this->heading     = __( 'New Product Submitted', 'wp-my-product-webspark' );
        $this->subject     = __( '[{site_title}] New Product: {product_name}', 'wp-my-product-webspark' );

        $this->template_base  = plugin_dir_path( __FILE__ ) . '../templates/';
        $this->template_html  = 'emails/admin-product-notification.php';
        $this->template_plain = 'emails/plain/admin-product-notification.php';

        add_action( 'wpmpw_product_submitted', array( $this, 'trigger' ), 10, 3 );

        parent::__construct();

        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    public function trigger( $product_id, $author_id, $is_update ) {
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $product = get_post( $product_id );
        $author  = get_user_by( 'ID', $author_id );

        $this->object = $product;

        $this->placeholders = array(
            '{site_title}'    => $this->get_blogname(),
            '{product_name}'  => $product->post_title,
        );

        $this->product_name = $product->post_title;
        $this->author_profile_url = admin_url( 'user-edit.php?user_id=' . $author_id );
        $this->edit_product_url   = admin_url( 'post.php?post=' . $product_id . '&action=edit' );

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'email_heading'      => $this->get_heading(),
                'product_name'       => $this->product_name,
                'author_profile_url' => $this->author_profile_url,
                'edit_product_url'   => $this->edit_product_url,
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
            )
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'email_heading'      => $this->get_heading(),
                'product_name'       => $this->product_name,
                'author_profile_url' => $this->author_profile_url,
                'edit_product_url'   => $this->edit_product_url,
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
            )
        );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'wp-my-product-webspark' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable this email notification', 'wp-my-product-webspark' ),
                'default'     => 'yes',
            ),
            'recipient' => array(
                'title'       => __( 'Recipient(s)', 'wp-my-product-webspark' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Enter recipients (comma separated). Defaults to %s.', 'wp-my-product-webspark' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'default'     => '',
            ),
        );
    }
}
