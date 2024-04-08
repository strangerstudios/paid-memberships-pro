/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	RichText,
	useBlockProps,
	__experimentalGetBorderClassesAndStyles as getBorderClassesAndStyles,
	__experimentalGetColorClassesAndStyles as getColorClassesAndStyles,
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	__experimentalGetElementClassName,
} from '@wordpress/block-editor';

export default function save( { attributes, className } ) {
	const {
		textAlign,
		style,
		text,
	} = attributes;

	const TagName = 'a';

	const url = ( level ) =>{
		return pmpro.checkout_url + '?pmpro_level=' + level;
	};

	// Get blockProps for use on the RichText.Content element
	const blockProps = useBlockProps.save();

	const borderProps = getBorderClassesAndStyles( attributes );
	const colorProps = getColorClassesAndStyles( attributes );
	const spacingProps = getSpacingClassesAndStyles( attributes );
	const buttonClasses = classnames(
		'wp-block-button__link',
		blockProps.className,
		colorProps.className,
		borderProps.className,
		{
			[ `has-text-align-${ textAlign }` ]: textAlign,
			// For backwards compatibility add style that isn't provided via
			// block support.
			'no-border-radius': style?.border?.radius === 0,
		},
		__experimentalGetElementClassName( 'button' )
	);
	const buttonStyle = {
		...blockProps.style,
		...borderProps.style,
		...colorProps.style,
		...spacingProps.style,
	};

	const wrapperButtonsDiv = classnames(
		'wp-block-buttons',
		{ [ `has-text-align-${ textAlign }` ]: textAlign },
	);

	const wrapperButtonDiv = classnames(
		'wp-block-button',
	);

	return (
		<div className={wrapperButtonsDiv}><div className={wrapperButtonDiv}>
			<RichText.Content
			  	{...blockProps}
				tagName={ TagName }
				className={ buttonClasses }
				href={ url( attributes.selected_membership_level ) }
				style={ buttonStyle }
				value={ text }
			/>
		</div></div>
	);
}
