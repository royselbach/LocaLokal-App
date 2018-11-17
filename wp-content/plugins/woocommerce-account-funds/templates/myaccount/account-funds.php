<?php
/**
 * My Account > Account Funds page
 *
 * @version 2.0.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wc_print_notices();

?>
<div class="woocommerce-MyAccount-account-funds">
	<p>
		<?php printf( __( 'You currently have <strong>%s</strong> worth of funds in your account.', 'woocommerce-account-funds' ), $funds ); ?>
	</p>

	<?php echo $topup; ?>
	<?php echo $products; ?>
	<?php echo $recent_deposits; ?>
</div>
