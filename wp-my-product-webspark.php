<?php
/**
 * Plugin Name: WP My Product WebSpark
 * Description: Розширення WooCommerce для роботи з продуктами через My Account.
 * Version: 1.0
 * Author: Iryna
 * Text Domain: wp-my-product-webspark
 */

if ( ! defined( 'ABSPATH' ) ) exit; 

function wpmpw_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__( 'WP My Product WebSpark вимагає активного WooCommerce.', 'wp-my-product-webspark' ) . '</p></div>';
        });
        return false;
    }
    return true;
}

function wpmpw_add_account_menu_items( $items ) {
    $items['add-product'] = __( 'Add Product', 'wp-my-product-webspark' );
    $items['my-products'] = __( 'My Products', 'wp-my-product-webspark' );
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'wpmpw_add_account_menu_items' );

function wpmpw_add_endpoints() {
    add_rewrite_endpoint( 'add-product', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'my-products', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'wpmpw_add_endpoints' );

function wpmpw_add_product_endpoint_content() {
    include plugin_dir_path( __FILE__ ) . 'templates/add-product.php';
}
add_action( 'woocommerce_account_add-product_endpoint', 'wpmpw_add_product_endpoint_content' );

function wpmpw_my_products_endpoint_content() {
    include plugin_dir_path( __FILE__ ) . 'templates/my-products.php';
}
add_action( 'woocommerce_account_my-products_endpoint', 'wpmpw_my_products_endpoint_content' );


function wpmpw_handle_product_submission() {
    if ( isset( $_POST['wpmpw_add_product'] ) ) {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpmpw_add_product' ) ) {
            return;
        }

        $product_name = sanitize_text_field( $_POST['product_name'] );
        $product_price = floatval( $_POST['product_price'] );
        $product_quantity = isset( $_POST['product_quantity'] ) ? intval( $_POST['product_quantity'] ) : 0;
        $product_description = wp_kses_post( $_POST['product_description'] );
        $product_image_id = intval( $_POST['product_image_id'] );

        $post_id = wp_insert_post( [
            'post_title'   => $product_name,
            'post_content' => $product_description,
            'post_status'  => 'pending',
            'post_type'    => 'product',
        ] );

        if ( $post_id ) {
            update_post_meta( $post_id, '_regular_price', $product_price );
            update_post_meta( $post_id, '_price', $product_price );
            update_post_meta( $post_id, '_product_quantity', $product_quantity );
            update_post_meta( $post_id, '_stock', $product_quantity );
            if ( $product_image_id ) {
                set_post_thumbnail( $post_id, $product_image_id );
            }
            do_action( 'wpmpw_product_submitted', $post_id, get_current_user_id(), false );

            wpmpw_notify_admin_on_product_save( $post_id );
           
        }
    }
}
add_action( 'init', 'wpmpw_handle_product_submission' );


function wpmpw_notify_admin_on_product_save( $post_id ) {
    $admin_email = get_option( 'admin_email' );
    $product = get_post( $post_id );

    $subject = __( 'New Product Pending Review', 'wp-my-product-webspark' );
    $message = sprintf(
        __( 'A new product "%s" is pending review. Author: %s. Edit: %s', 'wp-my-product-webspark' ),
        esc_html( $product->post_title ),
        esc_url( admin_url( 'user-edit.php?user_id=' . $product->post_author ) ),
        esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) )
    );

    wp_mail( $admin_email, $subject, $message );
}


function wpmpw_activate_plugin() {
    if ( wpmpw_check_woocommerce() ) {
        wpmpw_add_endpoints();
        flush_rewrite_rules();
    }
}
register_activation_hook( __FILE__, 'wpmpw_activate_plugin' );

function wpmpw_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wpmpw_deactivate_plugin' );


function enqueue_media_uploader() {
  
  wp_enqueue_media(); 
}
add_action( 'wp_enqueue_scripts', 'enqueue_media_uploader' ); 

function enqueue_custom_media_uploader_script() {

  wp_enqueue_script(
      'custom-media-uploader', 
      plugin_dir_url( __FILE__ ) . 'js/media-uploader.js', 
      array('jquery'), 
      null, 
      true 
  );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_media_uploader_script');

function wpmpw_handle_product_deletion() {
    if ( isset( $_POST['delete_product'] ) ) {
     
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpmpw_delete_product' ) ) {
            return;
        }

        $product_id = intval( $_POST['delete_product'] );

        if ( get_post_type( $product_id ) === 'product' && get_post_field( 'post_author', $product_id ) == get_current_user_id() ) {
            wp_delete_post( $product_id, true ); 
            wc_add_notice( __( 'Product deleted successfully.', 'wp-my-product-webspark' ), 'success' );
        } else {
            wc_add_notice( __( 'You are not allowed to delete this product.', 'wp-my-product-webspark' ), 'error' );
        }

        wp_safe_redirect( wc_get_account_endpoint_url( 'my-products' ) );
        exit;
    }
}
add_action( 'template_redirect', 'wpmpw_handle_product_deletion' );

////
add_filter( 'woocommerce_email_classes', 'wpmpw_register_admin_email' );
function wpmpw_register_admin_email( $email_classes ) {
    require_once 'includes/class-wc-email-admin-product-notification.php';
    $email_classes['WC_Email_Admin_Product_Notification'] = new WC_Email_Admin_Product_Notification();
    return $email_classes;
}

function wpmpw_add_quantity_field_to_product_editor() {
    global $post;

    echo '<div class="options_group">';

    woocommerce_wp_text_input( array(
        'id'          => '_product_quantity',
        'label'       => __( 'Product Quantity', 'wp-my-product-webspark' ),
        'placeholder' => 'Enter product quantity',
        'desc_tip'    => true,
        'description' => __( 'Set the available quantity for this product.', 'wp-my-product-webspark' ),
        'type'        => 'number',
        'custom_attributes' => array(
            'step' => '1',
            'min'  => '0',
        ),
    ) );

    echo '</div>';
}
add_action( 'woocommerce_product_options_inventory_product_data', 'wpmpw_add_quantity_field_to_product_editor' );

function wpmpw_save_quantity_field( $post_id ) {
    // Перевіряємо, чи є значення в $_POST
    if ( isset( $_POST['_product_quantity'] ) ) {
        $product_quantity = intval( $_POST['_product_quantity'] ); 
        update_post_meta( $post_id, '_product_quantity', $product_quantity ); 
    }
}
add_action( 'woocommerce_process_product_meta', 'wpmpw_save_quantity_field' );
