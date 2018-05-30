/**
 * Block: PMPro Membership
 *
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
    InnerBlocks,
} = wp.editor;


 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/membership',
     {
         title: __( 'PMPro Membership Check', 'paid-memberships-pro' ),
         description: __( 'Only shows content to specific levels.', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'hidden',
         keywords: [
         ],
         supports: {
           align: [ 'wide', 'full' ],
         },
         attributes: {
             levels: {
                 type: 'string',
             },
             hide: {
                 type: 'boolean',
                 default: false
             }
         },
         edit: props => {
             const { attributes: {levels, hide}, className, setAttributes, isSelected } = props;
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div className={ className } >
                  <h3>Click here to edit membership viewing options.</h3>
                  <InnerBlocks/>
                </div>
            ];
         },
         save() {
           const { className } = props;
           return (
        			<div className={ className }>
        				<InnerBlocks.Content />
        			</div>
        		);
         },
       }
 );
