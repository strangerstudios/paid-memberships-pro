/**
 * Block: PMPro Membership
 *
 *
 */

 /**
  * Internal block libraries
  */
 const { __ } = wp.i18n;
 const {
    registerBlockType,
} = wp.blocks;
const {
    PanelBody,
    SelectControl,
} = wp.components;

const {
    InspectorControls,
    InnerBlocks,
} = wp.blockEditor;

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
             const { attributes: {levels, uid}, setAttributes, isSelected } = props;
             if( uid=='' ) {
               var rand = Math.random()+"";
               setAttributes( { uid:rand } );
             }
             return [
                isSelected && <InspectorControls>
                    <PanelBody>
                        <SelectControl
                            multiple
                            label={ __( 'Select levels to show content to:', 'paid-memberships-pro' ) }
                            value={ levels }
                            onChange={ levels => { setAttributes( { levels } ) } }
                            options={ all_levels }
                        />
                    </PanelBody>
                </InspectorControls>,
                isSelected && <div className="pmpro-block-require-membership-element" >
                  <span className="pmpro-block-title">{ __( 'Require Membership', 'paid-memberships-pro' ) }</span>
                  <PanelBody>
                      <SelectControl
                          multiple
                          label={ __( 'Select levels to show content to:', 'paid-memberships-pro' ) }
                          value={ levels }
                          onChange={ levels => { setAttributes( { levels } ) } }
                          options={ all_levels }
                      />
                  </PanelBody>
                  <InnerBlocks
                      renderAppender={ () => (
                        <InnerBlocks.ButtonBlockAppender />
                      ) }
                      templateLock={ false }
                  />
                </div>,
                ! isSelected && <div className="pmpro-block-require-membership-element" >
                  <span className="pmpro-block-title">{ __( 'Require Membership', 'paid-memberships-pro' ) }</span>
                  <InnerBlocks
                      renderAppender={ () => (
                        <InnerBlocks.ButtonBlockAppender />
                      ) }
                      templateLock={ false }
                  />
                </div>,
            ];
         },
         save: props => {
           const {  className } = props;
        		return (
        			<div className={ className }>
        				<InnerBlocks.Content />
        			</div>
        		);
        	},
       }
 );
