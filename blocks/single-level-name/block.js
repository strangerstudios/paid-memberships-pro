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
    InnerBlocks,
    useBlockProps,
} = wp.blockEditor;
const {
    select
} = wp.data;

/**
 * Register block
 */
export default registerBlockType(
    'pmpro/single-level-name',
    {
        title: __('Level Name', 'paid-memberships-pro'),
        description: __('Nest blocks within this wrapper to control the inner block visibility by membership level or for non-members only.', 'paid-memberships-pro'),
        category: 'pmpro',
        icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'visibility',
        },
        parent: ['pmpro/single-level'],
        keywords: [
            __('block visibility', 'paid-memberships-pro'),
            __('conditional', 'paid-memberships-pro'),
            __('content', 'paid-memberships-pro'),
            __('hide', 'paid-memberships-pro'),
            __('hidden', 'paid-memberships-pro'),
            __('paid memberships pro', 'paid-memberships-pro'),
            __('pmpro', 'paid-memberships-pro'),
            __('private', 'paid-memberships-pro'),
            __('restrict', 'paid-memberships-pro'),
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

            const { setAttributes } = props;

            var parent = select('core/block-editor').getBlockParents(props.clientId);
            const parentAtts = select('core/block-editor').getBlockAttributes(parent);

            setAttributes({ selected_level: parentAtts.selected_level });

            let level_name = 'Level Name Placeholder';
            if (pmpro.all_levels_formatted_text[parentAtts.selected_level] !== undefined) {
                level_name = pmpro.all_levels_formatted_text[parentAtts.selected_level].name;
            }

            return (
                <div {...useBlockProps()}>
                    {level_name}
                </div>
            );
        },
        save: ( props ) => {
            
            const blockProps = useBlockProps.save();

            let level_name = 'Level Name Placeholder';
            
            if (pmpro.all_levels_formatted_text[props.attributes.selected_level] !== undefined) {
                level_name = pmpro.all_levels_formatted_text[props.attributes.selected_level].name;
            }
            
            return <div {...blockProps}>
                {level_name}
            </div>;
        },
    }
);
