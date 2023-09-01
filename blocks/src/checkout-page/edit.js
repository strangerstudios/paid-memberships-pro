/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps({});
    const postType = useSelect(
        ( select ) => select( 'core/editor' ).getCurrentPostType(),
        []
    );
    const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const pmpro_default_level_value = meta['pmpro_default_level'];
    const updateMetaValue = ( value ) => {
        setMeta( { ...meta, pmpro_default_level: value } );
    };

	return (
		<>
		<InspectorControls>
      <PanelBody>
          <SelectControl
              label={ __( 'Membership Level', 'paid-memberships-pro' ) }
              help={ __( 'Choose a default level for Membership Checkout.', 'paid-memberships-pro' ) }
              value={ pmpro_default_level_value }
              onChange={ updateMetaValue }
              options={ [''].concat( window.pmpro.all_level_values_and_labels ) }
          />
      </PanelBody>
      </InspectorControls>
      <div className="pmpro-block-element" { ...blockProps }>
      <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
	  <span className="pmpro-block-subtitle"> { __( 'Membership Checkout Form', 'paid-memberships-pro' ) }</span>
    </div>
		</>
	);
}

