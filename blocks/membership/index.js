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

var all_levels = [{ value: 0, label: "Non-Members" }];

function get_ajax_url() {
 var i = window.location.href.indexOf("/wp-admin/post.php?");

 if(i > 0)
  return  window.location.href.slice(0, i+10)+"admin-ajax.php";
 else
  return "";
}

jQuery(document).ready(function($) {
	var data = {
		'action': 'pmpro_getAllLevels',
	};

	var ajax_url = get_ajax_url();
  if ( ajax_url === "" ) {
    return;
  }
	jQuery.post(ajax_url, data, function(response) {
	  var temp_levels = JSON.parse(response.slice(0, -1));
    for (var level in temp_levels){
        if (temp_levels.hasOwnProperty(level)) {
             all_levels.push({ value: level, label: temp_levels[level] })
        }
    }
	});
});

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
                  <h3>PMPro Membership Block</h3>
                  <PanelBody>
                      <SelectControl
                          multiple
                          label={ __( 'Select levels to show content to:' ) }
                          value={ levels }
                          onChange={ levels => { setAttributes( { levels } ) } }
                          options={ all_levels }
                      />
                  </PanelBody>
                  <InnerBlocks />
                </div>,
                ! isSelected && <div className={ className } >
                  <h3>PMPro Membership Block</h3>
                  <InnerBlocks />
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
