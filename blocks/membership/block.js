/**
 * Block: PMPro Membership
 *
 *
 */
 /**
  * Block dependencies
  */
 import './editor.css';
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
    SelectControl,
} = wp.components;

const {
    RichText,
    InspectorControls,
    InnerBlocks,
} = wp.editor;

const all_levels = [{ value: 0, label: "Non-Members" }].concat( pmpro.all_level_values_and_labels );

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/membership',
     {
         title: __( 'Require Membership Block', 'paid-memberships-pro' ),
         description: __( 'Control the visibility of nested blocks for members or non-members.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'visibility',
         },
         keywords: [ __( 'pmpro', 'paid-memberships-pro' ) ],
         attributes: {
             levels: {
                 type: 'array',
                 default:[]
             },
             uid: {
                 type: 'string',
                 default:'',
             },
         },
         edit: props => {
             const { attributes: {levels, uid}, className, setAttributes, isSelected } = props;
             if( uid=='' ) {
               var rand = Math.random()+"";
               setAttributes( { uid:rand } );
             }
             return [
                isSelected && <InspectorControls>
                    <PanelBody>
                        <SelectControl
                            multiple
                            label={ __( 'Select levels to show content to:' ) }
                            value={ levels }
                            onChange={ levels => { setAttributes( { levels } ) } }
                            options={ all_levels }
                        />
                    </PanelBody>
                </InspectorControls>,
                isSelected && <div className={ className } >
                  <span class="pmpro-membership-title">Require Membership</span>
                  <PanelBody>
                      <SelectControl
                          multiple
                          label={ __( 'Select levels to show content to:' ) }
                          value={ levels }
                          onChange={ levels => { setAttributes( { levels } ) } }
                          options={ all_levels }
                      />
                  </PanelBody>
                  <InnerBlocks templateLock={ false } />
                </div>,
                ! isSelected && <div className={ className } >
                  <span class="pmpro-membership-title">Require Membership: { levels }</span>
                  <InnerBlocks templateLock={ false } />
                </div>,
            ];
         },
         save: props => {
           const { attributes: {levels, uid}, className, isSelected } = props;
        		return (
        			<div className={ className }>
        				<InnerBlocks.Content />
        			</div>
        		);
        	},
       }
 );
