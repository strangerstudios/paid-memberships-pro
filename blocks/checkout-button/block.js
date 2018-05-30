/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a
 * specific level.
 *
 * @todo : Remove link button from editor.
 * @todo : Add membership level setting or control.
 */
( function( blocks, i18n, element ) {
	var el = element.createElement;
	var __ = i18n.__;
	var Editable = blocks.Editable;
	var children = blocks.source.children;

	blocks.registerBlockType( 'paid-memberships-pro/checkout-button', {
		title: __( 'PMPro: Checkout Button', 'paid-memberships-pro' ),
		icon: 'groups',
		category: 'layout',

		attributes: {
			content: {
				type: 'array',
				source: 'children',
				selector: 'p',
			},
		},

		edit: function( props ) {
			var content = props.attributes.content;
			var focus = props.focus;
			function onChangeContent( newContent ) {
				props.setAttributes( { content: newContent } );
			}

			return el(
				Editable,
				{
					tagName: 'p',
					className: props.className,
					onChange: onChangeContent,
					value: content,
					focus: focus,
					onFocus: props.setFocus
				}
			);
		},

		save: function( props ) {
			return el( 'p', {}, props.attributes.content );
		},
	} );
} )(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.element
);