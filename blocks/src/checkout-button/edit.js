/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * Render the NAMEHERE block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( props ) {
	const blockProps = useBlockProps( {} );
	const { attributes, setAttributes, isSelected } = props;
	const { text, level, css_class } = attributes;

	return [
		<>
		<InspectorControls>
              <PanelBody>
                 <TextControl
                     label={ __( 'Button Text', 'paid-memberships-pro' ) }
                     help={ __( 'Text for checkout button', 'paid-memberships-pro' ) }
                     value={ text }
                     onChange={ text => setAttributes( { text } ) }
                 />
              </PanelBody>
              <PanelBody>
                  <SelectControl
                      label={ __( 'Level', 'paid-memberships-pro' ) }
                      help={ __( 'The level to link to for checkout button', 'paid-memberships-pro' ) }
                      value={ level }
                      onChange={ level => setAttributes( { level } ) }
                      options={ window.pmpro.all_level_values_and_labels }
                  />
              </PanelBody>
              <PanelBody>
                 <TextControl
                     label={ __( 'CSS Class', 'paid-memberships-pro' ) }
                     help={ __( 'Additional styling for checkout button', 'paid-memberships-pro' ) }
                     value={ css_class }
                     onChange={ css_class => setAttributes( { css_class } ) }
                 />
              </PanelBody>
          </InspectorControls>
		  <div { ...blockProps }>
                { /* Your Block Content here */ }
                { attributes.isSelected && (
                    <Inspector { ...{ setAttributes, ...attributes } } />
                )}
                <div className={ attributes.className }>
                    <a className={ attributes.css_class }>{ attributes.text }</a>
                </div>
                { attributes.isSelected && (
                    <div className="pmpro-block-element">
                        <TextControl
                            label={ __( 'Button Text', 'paid-memberships-pro' ) }
                            value={ attributes.text }
                            onChange={ (text) => setAttributes({ text }) }
                        />
                        <SelectControl
                            label={ __( 'Membership Level', 'paid-memberships-pro' ) }
                            value={ attributes.level }
                            onChange={ (level) => setAttributes({ level }) }
                            options={ window.pmpro.all_level_values_and_labels }
                        />
                        <TextControl
                            label={ __( 'CSS Class', 'paid-memberships-pro' ) }
                            value={ attributes.css_class }
                            onChange={ (css_class) => setAttributes({ css_class }) }
                        />
                    </div>
                )}
            </div>
		</>
	];
}
