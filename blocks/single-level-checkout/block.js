/**
 * Block: Single Level Checkout Button
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

 /**
  * Register block
  */
export default registerBlockType(
    'pmpro/single-level-checkout',
    {
        title: __( 'Level Checkout Button', 'paid-memberships-pro' ),
        description: __( 'A button to send users to the checkout page for this membership level.', 'paid-memberships-pro' ),
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
                    { 'Checkout Button?'}
                </div>
            );
        },
    }
);
