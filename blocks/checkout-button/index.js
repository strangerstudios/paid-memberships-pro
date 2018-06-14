/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a
 * specific level.
 *
 */
 /**
  * Block dependencies
  */
 import './style.scss';
 import classnames from 'classnames';
 import Inspector from './inspector';
 /**
  * Internal block libraries
  */
 const { __ } = wp.i18n;
 const {
    registerBlockType,
    BlockControls,
} = wp.blocks;
const {
    PanelBody,
    TextControl,
} = wp.components;

const {
    InspectorControls,
} = wp.editor;

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/checkout-button',
     {
         title: __( 'PMPro Checkout Button', 'paid-memberships-pro' ),
         description: __( 'Let users check out for a level.', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'migrate',
         keywords: [
             __( 'buy', 'paid-memberships-pro' ),
             __( 'level', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
             text: {
                 type: 'string',
                 default: 'Buy Now',
             },
             css_class: {
                 type: 'string',
                 default: 'wp-block-paid-memberships-pro-checkout-button',
             },
             level: {
                  type: 'integer'
             }
         },
         edit: props => {
             const { attributes: { text, level, css_class}, className, setAttributes, isSelected } = props;
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div
                    className={ className }
                >
                  <a class={css_class} >{text}</a>
                </div>,
                isSelected && <div>
                  <br/>
                   <TextControl
                       label={ __( 'Button Text', 'paid-memberships-pro' ) }
                       value={ text }
                       onChange={ text => setAttributes( { text } ) }
                   />
                   <TextControl
                       label={ __( 'Level ID', 'paid-memberships-pro' ) }
                       value={ level }
                       onChange={ level => setAttributes( { level } ) }
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
