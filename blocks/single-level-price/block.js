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
    useBlockProps, 
} = wp.blockEditor;

const getFormattedPrice = (level) => {
    return pmpro.all_levels_formatted_text[level]
                ? pmpro.all_levels_formatted_text[level].formatted_price
                : '[Level Price Placeholder]';
}

 /**
  * Register block
  */
export default registerBlockType(
    'pmpro/single-level-price',
    {
        title: __( 'Level Price', 'paid-memberships-pro' ),
        description: __( 'The price text for this membership level.', 'paid-memberships-pro' ),
        category: 'pmpro',
        icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'slides',
        },
        parent: ['pmpro/single-level'],
        keywords: [
            __('paid memberships pro', 'paid-memberships-pro'),
            __('pmpro', 'paid-memberships-pro'),
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
            return ( 
                <div { ...useBlockProps() }>
                    {
                        getFormattedPrice(props.attributes.selected_level) ?
                        <p>{ getFormattedPrice(props.attributes.selected_level) }</p> :
                        <p style={{color: "grey"}}>[Level Price Placeholder]</p>
                    }
                </div>
            );
        },
        save: ( props ) => {
            const blockProps = useBlockProps.save();
            return <div {...blockProps}>
               {getFormattedPrice(props.attributes.selected_level)}
            </div>;
        },
    }
);
