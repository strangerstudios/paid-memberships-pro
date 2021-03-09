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
    CheckboxControl,
} = wp.components;
const {
    useState,
} = wp.element;
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
             
             // Build an array of checkboxes for each level.
             var checkboxes = all_levels.map(function(level) {
                 const [ isChecked, setChecked ] = useState( level.value in levels );  
                                  
                 function setLevelsAttribute( checked ) {                     
                     if ( checked && ! ( level.value in levels ) ) {
                         // Add the level.                         
                     } else if ( ! checked && level.value in levels ) {
                         // Remove the level.                         
                     }
                                          
                     setChecked( level.value );
                     setAttributes( { levels } );                     
                 }
                 
                 return [                    
                    <CheckboxControl                    
                        label = { level.label }
                        checked = { isChecked }
                        onChange = { setLevelsAttribute( isChecked ) }
                    />
                 ]
             });
             
             return [
                isSelected && <InspectorControls>
                    <PanelBody>                        
                        <div class="pmpro-block-inspector-scrollable">
                            {checkboxes}
                        </div>
                    </PanelBody>
                </InspectorControls>,
                isSelected && <div className="pmpro-block-require-membership-element" >
                  <span className="pmpro-block-title">{ __( 'Require Membership', 'paid-memberships-pro' ) }</span>
                  <PanelBody>                      
                      {checkboxes}
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
