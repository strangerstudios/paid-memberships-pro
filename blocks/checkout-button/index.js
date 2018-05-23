/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a
 * specific level.
 *
 * @todo : Remove link button from editor.
 * @todo : Add membership level setting or control.
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
    AlignmentToolbar,
    BlockControls,
    BlockAlignmentToolbar,
} = wp.blocks;
const {
    PanelBody,
    PanelRow,
    TextControl,
} = wp.components;

const {
    RichText,
    InspectorControls,
} = wp.editor;

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/checkout-button',
     {
         title: __( 'Checkout Button', 'pmpro' ),
         description: __( 'Let users check out for a level.', 'pmpro' ),
         category: 'common',
         icon: 'cart',
         keywords: [
             __( 'buy', 'pmpro' ),
             __( 'level', 'pmpro' ),
         ],
         supports: {
         },
         attributes: {
             text: {
                 type: 'string',
                 default: 'Buy Now',
             },
             cssClass: {
                 type: 'string',
                 default: 'wp-block-paid-memberships-pro-checkout-button',
             },
             level: {
                  type: 'integer'
             }
         },
         edit: props => {
             const { attributes: { text, level, cssClass}, className, setAttributes, isSelected } = props;
             const link = ''; //use level to make link
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div
                    className={ className }
                >
                  <a href={link} class={cssClass}>{text}</a>
                </div>
            ];
         },
         save: props => {
           const { attributes: { text, level, cssClass}, className, setAttributes, isSelected } = props;
           const link = ''; //use level to make link
           return (
              <div className={ className }>
                <a href={link} class={cssClass}>{text}</a>
              </div>
          );
         },
     },
 );
