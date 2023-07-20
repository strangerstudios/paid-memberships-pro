/**
 * Block: PMPro Single Membership
 *
 *
 */
const getName = (level) => {
    return pmpro.all_levels_formatted_text[level]
                ? pmpro.all_levels_formatted_text[level].name
                : '[Level Name Placeholder]';
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
    'pmpro/single-level-name',
    {
        title: __('Level Name', 'paid-memberships-pro'),
        description: __('The name of this membership level.', 'paid-memberships-pro'),
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
            }
        },
        edit: props => {
            return (
                <div {...useBlockProps()}>
                    {
                        getName(props.attributes.selected_level) ?
                        <p>{ getName(props.attributes.selected_level) }</p> :
                        <p style={{color: "grey"}}>[Level Name Placeholder]</p>
                    }
                </div>
            );
        },
        save: ( props ) => {
            const blockProps = useBlockProps.save();
            return <div {...blockProps}>
                    {getName(props.attributes.selected_level)}
            </div>;
        },
    }
);
