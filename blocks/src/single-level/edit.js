/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const blockProps = useBlockProps( {} );
	const all_levels = [{ value: 0, label: __("Choose a level", 'paid-memberships-pro') }].concat(pmpro.all_level_values_and_labels);
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
		<>
		{isSelected && (
        <InspectorControls>
          <PanelBody>
            <SelectControl
              label={__("Select a level", 'paid-memberships-pro')}
              value={selected_level}
              options={all_levels}
              onChange={(selected_level) => setAttributes({ selected_level })}
            />
          </PanelBody>
        </InspectorControls>
      )}

		{isSelected ? (
        <div className="pmpro-block-require-membership-element" { ...blockProps }>
          <span className="pmpro-block-title">{__('Individual Membership Level', 'paid-memberships-pro')}</span>
			<div class="pmpro-block-inspector">
				<InnerBlocks templateLock={false} template={[
					['pmpro/single-level-name', { selected_level: selected_level, content: 'Example Nested Block Template' }],
					['pmpro/single-level-price', { selected_level: selected_level, content: 'Example Nested Block Template' }],
					['pmpro/single-level-expiration', {selected_level: selected_level, content: 'Example Nested Block Template' }],
					['pmpro/single-level-checkout', { selected_level: selected_level, content: 'Example Nested Block Template' }],
					['pmpro/single-level-description', { selected_level: selected_level, content: 'Example Nested Block Template' }],
				]}
				/>
			</div>
        </div>
      ) : (
        <div className="pmpro-block-require-membership-element" { ...blockProps }>
          <span className="pmpro-block-title">{__('Membership Level', 'paid-memberships-pro')}</span>
			<InnerBlocks templateLock={false} template={[
				['pmpro/single-level-name', { selected_level: selected_level, content: 'Example Nested Block Template' }],
				['pmpro/single-level-price', { selected_level: selected_level, content: 'Example Nested Block Template' }],
				['pmpro/single-level-expiration', {selected_level: selected_level, content: 'Example Nested Block Template' }],
				['pmpro/single-level-checkout', { selected_level: selected_level, content: 'Example Nested Block Template' }],
				['pmpro/single-level-description', { selected_level: selected_level, content: 'Example Nested Block Template' }],
			]}
			/>
        </div>
      )}		
		</>
	];
}
