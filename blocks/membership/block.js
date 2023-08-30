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
    registerBlockType
} = wp.blocks;
const {
    PanelBody,
    CheckboxControl,
    SelectControl,
} = wp.components;
const {
    InspectorControls,
    InnerBlocks,
} = wp.blockEditor;

const all_levels = [{ value: 0, label: __( "Non-Members", 'paid-memberships-pro' ) }].concat( pmpro.all_level_values_and_labels );

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/membership',
     {
         title: __( 'Membership Required Block', 'paid-memberships-pro' ),
         description: __( 'Nest blocks within this wrapper to control the inner block visibility by membership level or for non-members only.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'visibility',
         },
         keywords: [
            __( 'block visibility', 'paid-memberships-pro' ),
            __( 'conditional', 'paid-memberships-pro' ),
            __( 'content', 'paid-memberships-pro' ),
            __( 'hide', 'paid-memberships-pro' ),
            __( 'hidden', 'paid-memberships-pro' ),
            __( 'paid memberships pro', 'paid-memberships-pro' ),
            __( 'pmpro', 'paid-memberships-pro' ),
            __( 'private', 'paid-memberships-pro' ),
            __( 'restrict', 'paid-memberships-pro' ),
         ],
         attributes: {
             levels: {
                 type: 'array',
                 default:[]
             },
             uid: {
                 type: 'string',
                 default:'',
             },
             show_noaccess: {
                 type: 'string',
                 default: '0',
             },
         },
         edit: props => {
             const { attributes: {levels, uid, show_noaccess}, setAttributes, isSelected } = props;            
             if( uid=='' ) {
               var rand = Math.random()+"";
               setAttributes( { uid:rand } );
             }
             
             // Build an array of checkboxes for each level.
             var checkboxes = all_levels.map( function(level) {
                 function setLevelsAttribute( nowChecked ) {
                     if ( nowChecked && ! ( levels.some( levelID => levelID == level.value ) ) ) {
                        // Add the level.
                        const newLevels = levels.slice();
                        newLevels.push( level.value + '' );
                        setAttributes( { levels:newLevels } );
                     } else if ( ! nowChecked && levels.some( levelID => levelID == level.value ) ) {
                        // Remove the level.
                        const newLevels = levels.filter(( levelID ) => levelID != level.value);
                        setAttributes( { levels:newLevels } );
                     }
                 }
                 return [                    
                    <CheckboxControl
                        label = { level.label }
                        checked = { levels.some( levelID => levelID == level.value ) }
                        onChange = { setLevelsAttribute }
                    />
                 ]
             });
             
             return [
                isSelected && <InspectorControls>
                    <PanelBody>
                        <p><strong>{ __( 'Which membership levels can view this block?', 'paid-memberships-pro' ) }</strong></p>
                        <div className="pmpro-block-inspector-scrollable">
                            {checkboxes}
                        </div>
                        <hr />
                        <p><strong>{ __( 'What should users without access see?', 'paid-memberships-pro' ) }</strong></p>
                        <SelectControl
                          value={ show_noaccess }
                          help={__( "Modify the 'no access' message on the Memberships > Advanced Settings page.", "paid-memberships-pro" ) }
                          options={ [
                                { label: __( "Show nothing", 'paid-memberships-pro' ), value: '0' },
                                { label: __( "Show the 'no access' message", 'paid-memberships-pro' ), value: '1' },
                            ] }
                          onChange={ show_noaccess => setAttributes( { show_noaccess } ) }
                        />
                    </PanelBody>
                </InspectorControls>,
                isSelected && <div className="pmpro-block-require-membership-element" >
                  <span className="pmpro-block-title">{ __( 'Membership Required', 'paid-memberships-pro' ) }</span>
                  <div className="pmpro-block-inspector-scrollable">
                  <PanelBody>                      
                      {checkboxes}
                  </PanelBody>
                  </div>
                  <InnerBlocks
                      templateLock={ false }
                  />
                </div>,
                ! isSelected && <div className="pmpro-block-require-membership-element" >
                  <span className="pmpro-block-title">{ __( 'Membership Required', 'paid-memberships-pro' ) }</span>
                  <InnerBlocks
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
