/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * WordPress dependencies
 */
import {
	AlignmentControl,
	BlockControls,
	RichText,
	useBlockProps,
	InspectorControls,
	__experimentalUseBorderProps as useBorderProps,
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	__experimentalUseColorProps as useColorProps,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Render the Level Checkout Button block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const all_levels = [
		{ value: 0, label: __( 'Choose a level', 'paid-memberships-pro' ) },
	].concat(pmpro.all_level_values_and_labels);
	const {
		attributes,
		setAttributes,
		className,
	} = props;
	const {
		textAlign,
		placeholder,
		style,
		text,
		level,
		css_class,
		selected_membership_level,
	} = attributes;

	// Handle migration of the level attribute from PMPro < 3.0.
	if ( level && level.length > 0 ) {
		setAttributes({ selected_membership_level: level });

		// Delete the unused level attribute.
		delete attributes.level;
	}

	// Handle migration of the css_class attribute from PMPro < 3.0.
	if ( css_class && css_class.length > 0 ) {
		// Remove any 'pmpro_btn' strings from css_class.
		const cleanedCssClass = css_class.replace(/\bpmpro_btn\b/g, '').trim();

		// Merge previous attribute value for css_class with existing className.
		const migratedClassName = classnames( className, cleanedCssClass) ;
		setAttributes({ className: migratedClassName });

		// Delete the unused css_class attribute.
		delete attributes.css_class;
	}

	// Set the constant whether this block is a child of the Single Level block.
	const inSingleLevelBlock = useSelect( ( select ) => {
		// Retrieves the block editor's store
		const { getBlockParents, getBlockName } = select( 'core/block-editor' );

		// Gets all the ancestor blocks' client IDs
		const parentClientIds = getBlockParents( props.clientId );

		// Map parent client IDs to their block names
		const parentBlockNames = parentClientIds.map(parentId => getBlockName(parentId));

		// Check if the 'pmpro/single-level' block name is in the parent block names
		return parentBlockNames.includes('pmpro/single-level');
	}, [ props.clientId ] );

	function setButtonText( newText ) {
		setAttributes( { text: newText } );
	}

	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const blockProps = useBlockProps();

	const wrapperButtonsDiv = classnames(
		'wp-block-buttons',
		{ [ `has-text-align-${ textAlign }` ]: textAlign },
	);

	const wrapperButtonDiv = classnames(
		'wp-block-button',
	);

	return [
		<>
		<div className={wrapperButtonsDiv}><div className={wrapperButtonDiv}>
			{ ! inSingleLevelBlock && (
				<InspectorControls>
					<PanelBody
						title={__( 'Checkout Button Settings', 'paid-memberships-pro' ) }
						initialOpen={true}
					>
						<SelectControl
							label={__( 'Choose a Level', 'paid-memberships-pro' )}
							value={ selected_membership_level }
							options={ all_levels }
							onChange={(selected_membership_level) => setAttributes({ selected_membership_level })}
						/>
					</PanelBody>
				</InspectorControls>
			) }
			<BlockControls>
				<AlignmentControl
					value={ textAlign }
					onChange={ ( nextAlign ) => {
						setAttributes( { textAlign: nextAlign } );
					} }
				/>
			</BlockControls>
			<RichText
				{ ...blockProps }
				allowedFormats={ [] }
				aria-label={ __( 'Button text', 'paid-memberships-pro' ) }
				placeholder={ placeholder || __( 'Buy Now', 'paid-memberships-pro' ) }
				value={ text }
				onChange={ ( value ) => setButtonText( value ) }
				withoutInteractiveFormatting
				className={ classnames(
					className,
					'wp-block-button__link',
					blockProps.className,
					colorProps.className,
					borderProps.className,
					{
						// For backwards compatibility add style that isn't
						// provided via block support.
						'no-border-radius': style?.border?.radius === 0,
					},
					__experimentalGetElementClassName( 'button' )
				) }
				style={ {
					...blockProps.style,
					...borderProps.style,
					...colorProps.style,
					...spacingProps.style,
					textAlign: textAlign,
				} }
				identifier="text"
			/>
		</div></div>
		</>,
	];
}
