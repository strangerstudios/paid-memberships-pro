/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

/**
 * Register the Membership Levels and Pricing Table block.
 */
registerBlockType( metadata.name, {
	icon: {
		background: '#FFFFFF',
		foreground: '#658B24',
		src: 'list-view',
	},
	/**
	 * @see ./edit.js
	 */
	edit: Edit,
} );
