<?php do_action('event_manager_event_dashboard_before'); ?>
<div id="event-manager-event-dashboard">
				<table class="table table-bordered event-manager-events table-striped table-responsive" >
					<thead>
						<tr>
						  <?php foreach ( $event_dashboard_columns as $key => $column ) : ?>
							<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
						   <?php endforeach; ?>
						</tr>
					</thead>
			<tbody>
			<?php if ( ! $events ) : ?>
				<tr>
					<td colspan="6"><?php _e( 'You do not have any active listings.', 'wp-event-manager' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $events as $event ) : ?>
					<tr>
						<?php  foreach ( $event_dashboard_columns as $key => $column ) : ?>
							<td class="<?php echo esc_attr( $key ); ?>">
								<?php if ('event_title' === $key ) : ?>
									<?php if ( $event->post_status == 'publish' ) : ?>
										<a href="<?php echo get_permalink( $event->ID ); ?>"><?php echo esc_html( $event->post_title ); ?></a>
									<?php else : ?>
										<?php echo $event->post_title; ?> <small>(<?php display_event_status( $event ); ?>)</small>
									<?php endif; ?>
									<?php elseif ('event_action' === $key ) :?>
                                        <ul class="event-dashboard-actions" >
										<?php
											$actions = array();
											switch ( $event->post_status ) {
												case 'publish' :
													$actions['edit'] = array( 'label' => __( 'Edit', 'wp-event-manager' ), 'nonce' => false );
													if ( is_event_cancelled( $event ) ) {
														$actions['mark_not_cancelled'] = array( 'label' => __( 'Mark not cancelled', 'wp-event-manager' ), 'nonce' => true );
													} else {
														$actions['mark_cancelled'] = array( 'label' => __( 'Mark cancelled', 'wp-event-manager' ), 'nonce' => true );
													}
													$actions['duplicate'] = array( 'label' => __( 'Duplicate', 'wp-event-manager' ), 'nonce' => true );
													break;
												case 'expired' :
													if ( event_manager_get_permalink( 'submit_event_form' ) ) {
														$actions['relist'] = array( 'label' => __( 'Relist', 'wp-event-manager' ), 'nonce' => true );
													}
													break;
												case 'pending_payment' :
												case 'pending' :
													if ( event_manager_user_can_edit_pending_submissions() ) {
														$actions['edit'] = array( 'label' => __( 'Edit', 'wp-event-manager' ), 'nonce' => false );
												}
												break;
											}
											$actions['delete'] = array( 'label' => __( 'Delete', 'wp-event-manager' ), 'nonce' => true );
											$actions           = apply_filters( 'event_manager_my_event_actions', $actions, $event );
											foreach ( $actions as $action => $value ) {
												$action_url = add_query_arg( array( 'action' => $action, 'event_id' => $event->ID ) );
												if ( $value['nonce'] ) {
													$action_url = wp_nonce_url( $action_url, 'event_manager_my_event_actions' );
												}
												echo '<li><a href="' . esc_url( $action_url ) . '" class="event-dashboard-action-' . esc_attr( $action ) . '">' . esc_html( $value['label'] ) . '   </a></li>';
											}
										?>
									</ul>		
								<?php elseif ('event_start_date' === $key ) : 
											 $date = strtotime(get_event_start_date($event));
											 $time = get_event_start_time($event);
										 	 echo isset($date) ?  date("M j, Y",$date) . PHP_EOL : '-' . PHP_EOL;
											 echo (isset($time) && isset($date)) ? $time  : '-';
								?>
								<?php elseif ('event_end_date' === $key ) : 
											 $date = strtotime(get_event_end_date($event));
											 $time = get_event_end_time($event);
										 	 echo isset($date) ?  date("M j, Y",$date) . PHP_EOL : '-' . PHP_EOL;
											 echo (isset($time) && isset($date)) ? $time  : '-';		
								?>
            
                                <?php elseif ('event_location' === $key ) : 
                                     if(get_event_location($event)=='Anywhere'): echo __('Online Event','wp-event-manager'); else:  display_event_location(false,$event); endif;

    						?>
				<?php elseif ('view_count' === $key ) : 
											echo get_post_views_count($event); 
								?>
								<?php else : ?>
									<?php do_action( 'event_manager_event_dashboard_column_' . $key, $event ); ?>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php get_event_manager_template( 'pagination.php', array( 'max_num_pages' => $max_num_pages ) ); ?>
   </div>
   <?php do_action('event_manager_event_dashboard_after'); ?>