/**
 * Block: PMPro Single Membership
 *
 *
 */

const getExpirationText = (level) => {
    return pmpro.all_levels_formatted_text[level]
                ? pmpro.all_levels_formatted_text[level].formatted_expiration
                : '[Level Expiration Placeholder]';
}

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
    'pmpro/single-level-expiration',
    {
        title: __('Level Expiration Text', 'paid-memberships-pro'),
        description: __('The expiration text for this membership level.', 'paid-memberships-pro'),
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
                default: ''
            },
        },
        edit: props => {
            return (
                <div {...useBlockProps()}>
                    {
                        getExpirationText(props.attributes.selected_level) ?
                        <p>{ getExpirationText(props.attributes.selected_level) }</p> :
                        <p style={{color: "grey"}}>[Level Expiration Placeholder]</p>
                    }
                </div>
            );
        },
        save( props ) {

            const blockProps = useBlockProps.save();
            return <div {...blockProps}>
                {getExpirationText(props.attributes.selected_level)}
            </div>;
        },
    }
);
