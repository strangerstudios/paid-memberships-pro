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
	__experimentalUseBorderProps as useBorderProps,
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	__experimentalUseColorProps as useColorProps,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';

/**
 * Render the Level Checkout Button block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const {
		attributes,
		setAttributes,
		className,
	} = props;
	const {
		textAlign,
		style,
		placeholder,
		text,
	} = attributes;

	function setButtonText( newText ) {
		setAttributes( { text: newText } );
	}

	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const blockProps = useBlockProps();

	const wrapperClasses1 = classnames(
		'wp-block-buttons',
		{ [ `has-text-align-${ textAlign }` ]: textAlign },
	);

	const wrapperClasses2 = classnames(
		'wp-block-button',
	);

	return [
		<>
		<div className={wrapperClasses1}><div className={wrapperClasses2}>
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
				placeholder={ placeholder }
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
