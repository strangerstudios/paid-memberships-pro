<?php
/**
 * Template for Email Invoices
 *
 * @since 1.8.6
 */
?>
<table style="width:600px;margin-left:auto;margin-right:auto;">
	<thead>
	<tr>
		<td rowspan="2" style="width:80%;">
			<h2><?php bloginfo( 'sitename' ); ?></h2>
		</td>
		<td><?php echo __('Invoice #: ', 'pmpro') . '&nbsp;' . $order->code; ?></td>
	</tr>
	<tr>
		<td>
			<?php echo __( 'Date:', 'pmpro' ) . '&nbsp;' . date( 'Y-m-d', $order->timestamp ) ?>
		</td>
	</tr>
	<?php if(!empty($order->billing->name)): ?>
		<tr>
			<td>
				<strong><?php _e( 'Bill to:', 'pmpro' ); ?></strong><br>
				<?php
					echo pmpro_formatAddress(
						$order->billing->name,
						$order->billing->street,
						"",
						$order->billing->city,
						$order->billing->state,
						$order->billing->zip,
						$order->billing->country,
						$order->billing->phone
					); 
				?>
				<?php endif; ?>
			</td>
		</tr>
	</thead>
	<tbody>
	<tr>
		<td colspan="2">
			<table style="width:100%;border-width:1px;border-style:solid;border-collapse:collapse;">
				<tr style="border-width:1px;border-style:solid;border-collapse:collapse;">
					<th style="text-align:center;border-width:1px;border-style:solid;border-collapse:collapse;"><?php _e('ID', 'pmpro'); ?></th>
					<th style="border-width:1px;border-style:solid;border-collapse:collapse;"><?php _e('Item', 'pmpro'); ?></th>
					<th style="border-width:1px;border-style:solid;border-collapse:collapse;"><?php _e('Price', 'pmpro'); ?></th>
				</tr>
				<tr style="border-width:1px;border-style:solid;border-collapse:collapse;">
					<td style="text-align:center;border-width:1px;border-style:solid;border-collapse:collapse;"><?php echo $level->id; ?></td>
					<td style="border-width:1px;border-style:solid;border-collapse:collapse;"><?php echo $level->name; ?></td>
					<td style="text-align:right;"><?php echo $order->subtotal; ?></td>
				</tr>
				<tr style="border-width:1px;border-style:solid;border-collapse:collapse;">
					<th colspan="2" style="text-align:right;border-width:1px;border-style:solid;border-collapse:collapse;"><?php _e('Subtotal', 'pmpro'); ?></th>
					<td style="text-align:right;border-width:1px;border-style:solid;border-collapse:collapse;"><?php echo $order->subtotal; ?></td>
				</tr>
				<tr style="border-width:1px;border-style:solid;border-collapse:collapse;">
					<th colspan="2" style="text-align:right;border-width:1px;border-style:solid;border-collapse:collapse;"><?php _e('Tax', 'pmpro'); ?></th>
					<td style="text-align:right;border-width:1px;border-style:solid;border-collapse:collapse;"><?php echo $order->tax; ?></td>
				</tr>
				<tr style="border-width:1px;border-style:solid;border-collapse:collapse;">
					<th colspan="2" style="text-align:right;border-width:1px;border-style:solid;border-collapse:collapse;"><?php _e('Total', 'pmpro'); ?></th>
					<th style="text-align:right;border-width:1px;border-style:solid;border-collapse:collapse;"><?php echo pmpro_formatPrice($order->total); ?></th>
				</tr>
			</table>
		</td>
	</tr>
	</tbody>
</table>