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
    SelectControl,
} = wp.components;
const {
    InspectorControls,
    InnerBlocks,
    useBlockProps
} = wp.blockEditor;
const {
    dispatch,
    select
} = wp.data;

const all_levels = [{ value: 0, label: __("Choose a level", 'paid-memberships-pro') }].concat(pmpro.all_level_values_and_labels);

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
        edit: props => {
            const { attributes: { uid, selected_level }, setAttributes, isSelected } = props;
            // console.log(props);
            // console.log(props.clientId);
            var children = select('core/block-editor').getBlocksByClientId(props.clientId);
            //[0].innerBlocks;
            // console.log(children);
            children.forEach(function (child) {
                dispatch('core/block-editor').updateBlockAttributes(child.clientId, { selected_level: selected_level })
            });

            setAttributes({ selected_level: selected_level, levels: all_levels });

            if (uid == '') {
                var rand = Math.random() + "";
                setAttributes({ uid: rand });
            }

            return [
                isSelected && <InspectorControls>
                    <PanelBody>
                        <p><strong>{__('Select a Membership Level', 'paid-memberships-pro')}</strong></p>
                        <SelectControl
                            value={selected_level}
                            help={__("Select a level.", "paid-memberships-pro")}
                            options={all_levels}
                            onChange={selected_level => setAttributes({ selected_level })}
                        />
                    </PanelBody>
                </InspectorControls>,
                isSelected && <div className="pmpro-block-require-membership-element" >
                    <span className="pmpro-block-title">{__('Individual Membership Level', 'paid-memberships-pro')}</span>
                    <div class="pmpro-block-inspector">
                        <PanelBody>
                            <SelectControl
                                value={selected_level}
                                help={__("Select a level.", "paid-memberships-pro")}
                                options={all_levels}
                                onChange={selected_level => setAttributes({ selected_level })}
                            />
                        </PanelBody>
                    </div>
                    {/* <InnerBlocks templateLock={false} template={[
                        ['pmpro/single-level-name', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-price', { level: 2, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-expiration', { level: 2, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-description', { level: 2, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-checkout', { level: 2, content: 'Example Nested Block Template' }],
                    ]}
                    /> */}
                </div>,
                !isSelected && <div className="pmpro-block-require-membership-element" >
                    <span className="pmpro-block-title">{__('Membership Level', 'paid-memberships-pro')}</span>
                    <InnerBlocks templateLock={false} template={[
                        ['pmpro/single-level-name', { selected_level: selected_level, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-price', { level: 2, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-expiration', { level: 2, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-description', { level: 2, content: 'Example Nested Block Template' }],
                        ['pmpro/single-level-checkout', { level: 2, content: 'Example Nested Block Template' }],
                    ]}
                    />
                </div>,
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
