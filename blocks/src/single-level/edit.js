/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, SelectControl } from "@wordpress/components";
import { select, dispatch } from "@wordpress/data";

/**
 * Render the Single Level Block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const all_levels = [
		{ value: 0, label: __("Choose a level", "paid-memberships-pro") },
	].concat(pmpro.all_level_values_and_labels);
	const {
		attributes: { layout, selected_membership_level },
		setAttributes,
		isSelected,
	} = props;

	// Default to constrained layout if not set.
	if ( ! layout ) {
		setAttributes( { layout: { type: 'constrained' } } );
	}

	const element = select("core/block-editor").getBlock(props.clientId);
	element.innerBlocks.forEach((child) => {
		dispatch("core/block-editor").updateBlockAttributes(child.clientId, {
			selected_membership_level: selected_membership_level,
		});
	});

	return [
		isSelected && (
			<InspectorControls>
				<PanelBody>
					<SelectControl
						label={__("Select a level", "paid-memberships-pro")}
						value={selected_membership_level}
						options={all_levels}
						onChange={(selected_membership_level) => setAttributes({ selected_membership_level })}
					/>
				</PanelBody>
			</InspectorControls>
		),
		<div {...useBlockProps()} >
			<InnerBlocks
				templateLock={false}
				template={[
					[
						"pmpro/single-level-name",
						{
							selected_membership_level: selected_membership_level
						},
					],
					[
						"pmpro/single-level-price",
						{
							selected_membership_level: selected_membership_level
						},
					],
					[
						"pmpro/checkout-button",
						{
							selected_membership_level: selected_membership_level
						},
					],
					[
						"pmpro/single-level-expiration",
						{
							selected_membership_level: selected_membership_level
						},
					],
					[
						"pmpro/single-level-description",
						{
							selected_membership_level: selected_membership_level
						},
					],
				]}
			/>
		</div>
	];
}
