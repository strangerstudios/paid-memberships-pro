/**
 * The item editor on the edit order and edit subscription pages.
 *
 * Shows the items' total. For an order, shows whether the items add up to the order's subtotal and offers to fix
 * the difference by changing one item, or by setting the subtotal to the items' total.
 */
jQuery( document ).ready( function( $ ) {
	var currency = pmproItemsEditor.currency;
	var text = pmproItemsEditor.text;

	function round( amount ) {
		var factor = Math.pow( 10, currency.decimals );
		return Math.round( amount * factor ) / factor;
	}

	function formatPrice( amount ) {
		var parts = Math.abs( amount ).toFixed( currency.decimals ).split( '.' );
		var number = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, currency.thousands_separator ) + ( parts[1] ? currency.decimal_separator + parts[1] : '' );
		var price;
		if ( 'right' === currency.position ) {
			price = number + currency.symbol;
		} else if ( 'right_space' === currency.position ) {
			price = number + ' ' + currency.symbol;
		} else if ( 'left_space' === currency.position ) {
			price = currency.symbol + ' ' + number;
		} else {
			price = currency.symbol + number;
		}
		return ( amount < 0 ? '-' : '' ) + price;
	}

	function parseAmount( value ) {
		var amount = parseFloat( String( value ).replace( currency.decimal_separator, '.' ) );
		return isNaN( amount ) ? 0 : amount;
	}

	$( '.pmpro_items_editor' ).each( function() {
		var $editor = $( this );
		var isOrder = 'order' === $editor.data( 'context' );
		var $subtotal = $( '#subtotal' );
		var $total = $( '#total' );
		var $edited = $editor.find( '.pmpro_items_editor_edited' );
		var nextIndex = $editor.find( '.pmpro_items_editor_row' ).length;

		function update() {
			var total = 0;
			$editor.find( '.pmpro_items_editor_amount' ).each( function() {
				total += parseAmount( $( this ).val() );
			} );
			total = round( total );
			$editor.find( '.pmpro_items_editor_total' ).text( formatPrice( total ) );

			// A subscription's items aren't compared to what the gateway bills, since that may include tax.
			if ( ! isOrder ) {
				return;
			}

			var difference = round( parseAmount( $subtotal.val() ) - total );
			$editor.toggleClass( 'pmpro_items_editor-off', 0 !== difference );

			// Offer each item that can take the difference without turning a charge into a credit or the reverse.
			$editor.find( '.pmpro_items_editor_row' ).each( function() {
				var $amount = $( this ).find( '.pmpro_items_editor_amount' );
				var current = parseAmount( $amount.val() );
				var fixed = round( current + difference );
				var keepsSign = 0 === current || 0 === fixed || ( current > 0 ) === ( fixed > 0 );
				$( this ).find( '.pmpro_items_editor_fix' )
					.prop( 'hidden', 0 === difference || ! keepsSign )
					.text( text.make_it.replace( '%s', formatPrice( fixed ) ) )
					.data( 'amount', fixed.toFixed( currency.decimals ) );
			} );

			var message;
			if ( 0 === difference ) {
				message = text.matches.replace( '%s', text.order_target );
			} else {
				message = ( difference < 0 ? text.more : text.less ).replace( '%1$s', formatPrice( Math.abs( difference ) ) ).replace( '%2$s', text.order_target ) + ' ' + text.order_hint;
			}
			$editor.find( '.pmpro_items_editor_message' ).text( message );
			$editor.find( '.pmpro_items_editor_set_target' )
				.prop( 'hidden', 0 === difference )
				.text( text.set_subtotal.replace( '%s', formatPrice( total ) ) )
				.data( 'amount', total );
		}

		function edited() {
			$edited.val( '1' );
			update();
		}

		$editor.on( 'input change', 'input', edited );

		$editor.on( 'click', '.pmpro_items_editor_fix', function() {
			$( this ).closest( '.pmpro_items_editor_row' ).find( '.pmpro_items_editor_amount' ).val( $( this ).data( 'amount' ) );
			edited();
		} );

		$editor.on( 'click', '.pmpro_items_editor_remove', function() {
			$( this ).closest( '.pmpro_items_editor_row' ).remove();
			edited();
		} );

		$editor.on( 'click', '.pmpro_items_editor_add', function() {
			var row = $editor.find( '.pmpro_items_editor_template' ).html().replace( /__INDEX__/g, nextIndex++ );
			$editor.find( 'tbody' ).append( row );
			$editor.find( '.pmpro_items_editor_row' ).last().find( 'input[type="text"]' ).first().trigger( 'focus' );
			edited();
		} );

		// Setting the subtotal moves the total by the same amount, so the tax doesn't change.
		$editor.on( 'click', '.pmpro_items_editor_set_target', function() {
			var amount = parseFloat( $( this ).data( 'amount' ) );
			var change = amount - parseAmount( $subtotal.val() );
			$subtotal.val( amount.toFixed( currency.decimals ) );
			if ( $total.length ) {
				$total.val( round( parseAmount( $total.val() ) + change ).toFixed( currency.decimals ) );
			}
			update();
		} );

		if ( isOrder ) {
			$subtotal.on( 'input change', update );
		}

		update();
	} );
} );
