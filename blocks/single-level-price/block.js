/**
 * Block: PMPro Single Membership
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
    useBlockProps, 
} = wp.blockEditor;
const {
    dispatch,
    select
} = wp.data;

 /**
  * Register block
  */
export default registerBlockType(
    'pmpro/single-level-price',
    {
        title: __( 'Level Price', 'paid-memberships-pro' ),
        description: __( 'Nest blocks within this wrapper to control the inner block visibility by membership level or for non-members only.', 'paid-memberships-pro' ),
        category: 'pmpro',
        icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'visibility',
        },
        parent: ['pmpro/single-level'],
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
                default: []
            },
            selected_level: {
                type: 'string',
                default:''
            },
        },
        edit: props => {
            
            const { attributes: { levels, selected_level }, setAttributes, isSelected } = props; 

            var parent = select('core/block-editor').getBlockParents(props.clientId);
            const parentAtts = select('core/block-editor').getBlockAttributes(parent);

            setAttributes( {selected_level: parentAtts.selected_level } );

            const formatted_price = pmpro.all_levels_formatted_text[parentAtts.selected_level].formatted_price;

            return ( 
                <div { ...useBlockProps() }>
                    { formatted_price }
                </div>
            );
        },
        save: props => {
            
            const {  className } = props;
            
            const blockProps = useBlockProps.save();

            return (
                <div { ...blockProps }>
                    <InnerBlocks.Content />
                </div>
            );
        },
    }
);
