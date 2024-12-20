<?php
// Проверка, если пользователь авторизован
if ( ! is_user_logged_in() ) {
    echo 'You need to be logged in to add a product.';
    return;
}

?>

<form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field( 'add_product_nonce' ); ?>

    <p>
        <label for="product_name"><?php _e( 'Product Name', 'wp-my-product-webspark' ); ?></label>
        <input type="text" name="product_name" id="product_name" required />
    </p>
    <p>
        <label for="product_price"><?php _e( 'Product Price', 'wp-my-product-webspark' ); ?></label>
        <input type="number" name="product_price" id="product_price" required />
    </p>
    <p>
        <label for="product_quantity"><?php _e( 'Product Quantity', 'wp-my-product-webspark' ); ?></label>
        <input type="number" name="product_quantity" id="product_quantity" required />
    </p>
    <p>
        <label for="product_description"><?php _e( 'Product Description', 'wp-my-product-webspark' ); ?></label>
        <?php wp_editor( '', 'product_description', array( 'textarea_name' => 'product_description' ) ); ?>
    </p>
    <p>
        <label for="product_image"><?php _e( 'Product Image', 'wp-my-product-webspark' ); ?></label>
        <input type="text" name="product_image" id="product_image" />
        <button type="button" id="product_image_button"><?php _e( 'Select Image', 'wp-my-product-webspark' ); ?></button>
    </p>
    <p>
        <input type="submit" name="submit_product" value="<?php _e( 'Add Product', 'wp-my-product-webspark' ); ?>" />
    </p>
</form>

<?php
 if ( isset( $_POST['submit_product'] ) ) {

    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'add_product_nonce' ) ) {
        return;
    }

    $product_name = sanitize_text_field( $_POST['product_name'] );
    $product_price = floatval( $_POST['product_price'] );
    $product_quantity = intval( $_POST['product_quantity'] );
    $product_description = wp_kses_post( $_POST['product_description'] );
    $product_image_url = sanitize_text_field( $_POST['product_image'] );

    $post_data = array(
        'post_title'   => $product_name,
        'post_content' => $product_description,
        'post_status'  => 'pending', 
        'post_type'    => 'product',
    );

   
    $post_id = wp_insert_post( $post_data );

    if ( $post_id ) {
     
        update_post_meta( $post_id, '_regular_price', $product_price ); 
        update_post_meta( $post_id, '_price', $product_price ); 
        update_post_meta( $post_id, '_stock', $product_quantity ); 
        update_post_meta( $post_id, '_product_quantity', $product_quantity );

        if ( ! empty( $product_image_url ) ) {
            $attachment_id = attachment_url_to_postid( $product_image_url );
            if ( $attachment_id ) {
                set_post_thumbnail( $post_id, $attachment_id ); 
            }
        }

        wpmpw_notify_admin_on_product_save( $post_id );
    }
}

