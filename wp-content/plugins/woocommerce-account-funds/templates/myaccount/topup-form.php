<form method="post">
	<h3><label for="topup_amount"><?php _e( 'Top-up Account Funds', 'woocommerce-account-funds' ); ?></label></h3>
	<p class="form-row form-row-first">
		<input type="number" class="input-text" name="topup_amount" id="topup_amount" step="0.01" value="<?php echo esc_attr( $min_topup ); ?>" min="<?php echo esc_attr( $min_topup ); ?>" max="<?php echo esc_attr( $max_topup ); ?>" />
		<?php if ( $min_topup || $max_topup ) : ?>
		<span class="description">
			<?php
			printf(
				'%s%s%s',
				$min_topup ? sprintf( __( 'Minimum: <strong>%s</strong>.', 'woocommerce-account-funds' ), wc_price( $min_topup ) ) : '',
				$min_topup && $max_topup ? ' ' : '',
				$max_topup ? sprintf( __( 'Maximum: <strong>%s</strong>.', 'woocommerce-account-funds' ), wc_price( $max_topup ) ) : ''
			);
			?>
		</span>
		<?php endif; ?>
	</p>
	<p class="form-row">
		<input type="hidden" name="wc_account_funds_topup" value="true" />
		<input type="submit" class="button" value="<?php _e( 'Top-up', 'woocommerce-account-funds' ); ?>" />
	</p>
	<?php wp_nonce_field( 'account-funds-topup' ); ?>
</form>
