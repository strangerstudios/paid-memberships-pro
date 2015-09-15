<?php
/**
 * Template for Order Print Views
 *
 * @since 1.8.6
 */
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo __( 'Invoice for Order', 'pmpro' ) . '&nbsp;' . $order->code; ?></title>
	<style>
		div {
			width: 66.6%;
			margin: 0 auto;
		}
		.header-right  {
			display: inline;
			float: right;
			clear: right;
		}
		.right {
			text-align: right;
		}
		.center {
			text-align: center;
		}
		table {
			width: 100%;
			margin: 1em 0;
		}
		table, tr, td, th {
			border: 1px solid;
			border-collapse: collapse;
		}
		td, th {
			padding: 5px;
		}
	</style>
</head>
<body>
<div>
	<h2><?php bloginfo( 'sitename' ); ?></h2>
	<span class="header-right"><?php echo __('Invoice #: ', 'pmpro') . '&nbsp;' . $order->code; ?></span>
	<span class="header-right">
		<?php echo __( 'Date:', 'pmpro' ) . '&nbsp;' . date( 'Y-m-d', $order->timestamp ) ?>
	</span>
	<?php if(!empty($order->billing->name)): ?>
		<strong><?php _e( 'Bill to:', 'pmpro' ); ?></strong><br>
		<?php echo pmpro_formatAddress(
		$order->billing->name,
		$order->billing->address1,
		$order->billing->address2,
		$order->billing->city,
		$order->billing->state,
		$order->billing->zip,
		$order->billing->country,
		$order->billing->phone
	); ?>
	<?php endif; ?>
	<table>
		<thead>
		<tr>
			<th class="center"><?php _e('ID', 'pmpro'); ?></th>
			<th><?php _e('Item', 'pmpro'); ?></th>
			<th><?php _e('Price', 'pmpro'); ?></th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td class="center"><?php echo $level->id; ?></td>
			<td><?php echo $level->name; ?></td>
			<td class="right"><?php echo $order->subtotal; ?></td>
		</tr>
		<tr>
			<th colspan="2" class="right"><?php _e('Subtotal', 'pmpro'); ?></th>
			<td class="right"><?php echo $order->subtotal; ?></td>
		</tr>
		<tr>
			<th colspan="2" class="right"><?php _e('Tax', 'pmpro'); ?></th>
			<td class="right"><?php echo $order->tax; ?></td>
		</tr>
		<tr>
			<th colspan="2" class="right"><?php _e('Total', 'pmpro'); ?></th>
			<th class="right"><?php echo pmpro_formatPrice($order->total); ?></th>
		</tr>
		</tbody>
	</table>

</div>
</body>
</html>