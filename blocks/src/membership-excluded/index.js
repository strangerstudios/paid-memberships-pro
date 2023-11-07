/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

/**
 * Register the Membership Excluded block.
 */
registerBlockType( metadata.name, {
	icon: {
		background: '#FFFFFF',
		foreground: '#1A688B',
		src: 'hidden',
	},
	/**
	 * @see ./edit.js
	 */
	edit: Edit,
	/**
	 * @see ./save.js
	 */
	save: Save,
} );
