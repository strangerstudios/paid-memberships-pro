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
                 type: 'array',
                 source: 'children',
                 selector: '.message-body',
                 default: 'Buy Now',
             },
             level: {
                  type: 'integer'
             }
         },
         edit: props => {
             const { attributes: { text, level}, className, setAttributes } = props;

             return [(
                     <InspectorControls>
                         <PanelBody>
                            <TextControl
                                label={ __( 'Text', 'pmpro' ) }
                                help={ __( 'Text for checkout button', 'pmpro' ) }
                                value={ text }
                                onChange={ text => setAttributes( { text } ) }
                            />
                         </PanelBody>
                         <PanelBody>
                            <TextControl
                                label={ __( 'Level', 'pmpro' ) }
                                help={ __( 'Level id to check out', 'pmpro' ) }
                                value={ level }
                                onChange={ level => setAttributes( { level } ) }
                            />
                         </PanelBody>
                     </InspectorControls>
                 ),
                 <div
                     className={ classnames(
                         props.className,
                     ) }
                 >
                     <RichText
                         tagName="div"
                         multiline="p"
                         value={ text }
                         onChange={ ( text ) => setAttributes( { text } ) }
                     />
                 </div>
             ];
         },
         save: props => {
             const { attributes: { text } } = props;
             return (
                 <div>
                     <h2>{ __( 'Call to Action', 'pmpro' ) }</h2>
                     <div class="message-body">
                         { text }
                     </div>
                 </div>
             );
         },
     },
 );
