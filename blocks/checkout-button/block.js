/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a specific level.
 *
 */

import metadata from './block.json';

/**
 * Block dependencies
 */
import Inspector from './inspector';

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const {
    registerBlockType,
} = wp.blocks;
const {
    TextControl,
    SelectControl,
} = wp.components;

/**
 * Register block
 */
export default registerBlockType(
     metadata,
     {
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: metadata.icon,
         },
         edit: props => {
             const { attributes: { text, level, css_class}, className, setAttributes, isSelected } = props;
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div className={ className }>
                  <a class={css_class} >{text}</a>
                </div>,
                isSelected && <div className="pmpro-block-element">
                   <TextControl
                       label={ __( 'Button Text', 'paid-memberships-pro' ) }
                       value={ text }
                       onChange={ text => setAttributes( { text } ) }
                   />
                   <SelectControl
                       label={ __( 'Membership Level', 'paid-memberships-pro' ) }
                       value={ level }
                       onChange={ level => setAttributes( { level } ) }
                       options={ window.pmpro.all_level_values_and_labels }
                   />
                   <TextControl
                       label={ __( 'CSS Class', 'paid-memberships-pro' ) }
                       value={ css_class }
                       onChange={ css_class => setAttributes( { css_class } ) }
                   />
                   </div>,
            ];
         },
         save() {
           return null;
         },
       }
);