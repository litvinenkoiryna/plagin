<?php
// Перевірка, чи користувач авторизований
if ( ! is_user_logged_in() ) {
    echo 'You need to be logged in to view your products.';
    return;
}

// Отримання продуктів користувача
$user_id = get_current_user_id();
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 15,
    'paged'          => get_query_var( 'paged', 1 ),
    'author'         => $user_id,
    'post_status'    => 'any',
);

$query = new WP_Query( $args );

if ( $query->have_posts() ) :
    ?>
    <table>
        <thead>
            <tr>
                <th><?php _e( 'Product Name', 'wp-my-product-webspark' ); ?></th>
                <th><?php _e( 'Quantity', 'wp-my-product-webspark' ); ?></th>
                <th><?php _e( 'Price', 'wp-my-product-webspark' ); ?></th>
                <th><?php _e( 'Status', 'wp-my-product-webspark' ); ?></th>
                <th><?php _e( 'Actions', 'wp-my-product-webspark' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <tr>
                    <td><?php the_title(); ?></td>
                    <td><?php echo get_post_meta( get_the_ID(), '_stock', true ); ?></td>
                    <td><?php echo get_post_meta( get_the_ID(), '_price', true ); ?></td>
                    <td><?php echo get_post_status(); ?></td>
                    <td>
                        <a href="<?php echo get_edit_post_link(); ?>"><?php _e( 'Edit', 'wp-my-product-webspark' ); ?></a>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'wpmpw_delete_product' ); ?>
                            <input type="hidden" name="delete_product" value="<?php the_ID(); ?>" />
                            <input type="submit" value="<?php _e( 'Delete', 'wp-my-product-webspark' ); ?>" />
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
    // Пагінація
    echo paginate_links( array(
        'total' => $query->max_num_pages,
    ) );
else :
    echo 'You have no products.';
endif;
?>
