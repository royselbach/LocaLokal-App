<?php
/**
 * My Account > Account Funds page
 *
 * @version 2.0.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$pre_wc_30 = version_compare( WC_VERSION, '3.0.0', '<' );
?>
<h2><?php _e( 'Recent Deposits', 'woocommerce-account-funds' ); ?></h2>
<table class="shop_table my_account_deposits">
	<thead>
		<tr>
			<th class="order-number"><span class="nobr"><?php _e( 'Order', 'woocommerce-account-funds' ); ?></span></th>
			<th class="order-date"><span class="nobr"><?php _e( 'Date', 'woocommerce-account-funds' ); ?></span></th>
			<th class="order-total"><span class="nobr"><?php _e(' Status', 'woocommerce-account-funds' ); ?></span></th>
			<th class="order-status"><span class="nobr"><?php _e( 'Amount Funded', 'woocommerce-account-funds' ); ?></span></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $deposits as $deposit ) :
			$order = wc_get_order( $deposit->ID );

			$funded = 0;

			foreach ( $order->get_items() as $item ) {
				$product = null;
				if ( $pre_wc_30 ) {
					$product = $order->get_product_from_item( $item );
					if ( ! $product && ! empty( $item['top_up_amount'] ) ) {
						$product = new WC_Product_Topup( 0 );
					}
				} else {
					if ( $item->is_type( 'line_item' ) ) {
						$product = $item->get_product();
					}
				}

				if ( ! $product ) {
					continue;
				}

				if ( $product->is_type( 'deposit' ) || $product->is_type( 'topup' ) ) {
					$funded += $order->get_line_total( $item );
				}
			}

			$order_date = version_compare( WC_VERSION, '3.0', '<' ) ? $order->order_date : ( $order->get_date_created() ? gmdate( 'Y-m-d H:i:s', $order->get_date_created()->getOffsetTimestamp() ) : '' );
			?><tr class="order">
			<td class="order-number" data-title="<?php _e( 'Order Number', 'woocommerce-account-funds' ); ?>">
				<a href="<?php echo $order->get_view_order_url(); ?>">
					#<?php echo $order->get_order_number(); ?>
				</a>
			</td>
			<td class="order-date" data-title="<?php _e( 'Date', 'woocommerce-account-funds' ); ?>">
				<time datetime="<?php echo date( 'Y-m-d', strtotime( $order_date ) ); ?>" title="<?php echo esc_attr( strtotime( $order_date ) ); ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order_date ) ); ?></time>
			</td>
			<td class="order-status" data-title="<?php _e( 'Status', 'woocommerce-account-funds' ); ?>" style="text-align:left; white-space:nowrap;">
				<?php echo wc_get_order_status_name( $order->get_status() ); ?>
			</td>
			<td class="order-total">
				<?php echo wc_price( $funded ); ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

