<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Шаблон листа адміністратору після створення/редагування продукту.
 */

echo '= ' . esc_html( $email_heading ) . " =\n\n";

echo sprintf(
    __( 'Product "%s" has been submitted for review.', 'wp-my-product-webspark' ),
    $product_name
);
echo "\n\n";

echo __( 'Author profile:', 'wp-my-product-webspark' ) . ' ' . esc_url( $author_profile_url ) . "\n";
echo __( 'Edit product:', 'wp-my-product-webspark' ) . ' ' . esc_url( $edit_product_url ) . "\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
