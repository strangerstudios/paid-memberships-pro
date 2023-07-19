/**
 * Block: PMPro Single Membership
 *
 *
 */

/**
 * Block dependencies
 */
import Inspector from './inspector';

/**
 * Internal block libraries
 */

const { __ } = wp.i18n;
const {
    registerBlockType
} = wp.blocks;
const {
    InnerBlocks,
    useBlockProps
} = wp.blockEditor;
const {
    dispatch,
    select
} = wp.data;

/**
 * Register block
 */
export default registerBlockType(
    'pmpro/single-level',
    {
        title: __('Single Membership Level', 'paid-memberships-pro'),
        description: __('Holds single membership level parts', 'paid-memberships-pro'),
        category: 'pmpro',
        icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'visibility',
        },
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
            uid: {
                type: 'string',
                default: '',
            },
            selected_level: {
                type: 'string',
                default: ''
            }
        },
        edit: (props) => {
            const {
              attributes: {  selected_level },
              setAttributes,
              isSelected,
            } = props

            const element = select('core/block-editor').getBlock(props.clientId);
            element.innerBlocks.forEach((child) => {
                dispatch('core/block-editor').updateBlockAttributes(child.clientId, {
                selected_level: selected_level,
                });
            });

            return [
                isSelected && <div className="pmpro-block-require-membership-element" >
                    <span className="pmpro-block-title">{__('Individual Membership Level', 'paid-memberships-pro')}</span>
                    <div class="pmpro-block-inspector">
                        <InnerBlocks templateLock={false} template={[
                            ['pmpro/single-level-name', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                            ['pmpro/single-level-price', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                            ['pmpro/single-level-expiration', {selected_level: selected_level, content: 'Example Nested Block Template' }],
                            ['pmpro/single-level-description', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                            ['pmpro/single-level-checkout', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ]}
                        />
                    </div>
                </div>,
                !isSelected && <div className="pmpro-block-require-membership-element" >
                    <span className="pmpro-block-title">{__('Membership Level', 'paid-memberships-pro')}</span>
                    <InnerBlocks templateLock={false} template={[
                        ['pmpro/single-level-name', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-price', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-expiration', {selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-description', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-checkout', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                    ]}
                    />
                </div>,
                isSelected && <Inspector {...props} />,
            ];
        },
        save() {
            const blockProps = useBlockProps.save();
            return (
                <div {...blockProps}>
                    <InnerBlocks.Content />
                </div>
            );
        },
    }
);
