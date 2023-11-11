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

	if ( ! text ) {
		return null;
	}

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
		...borderProps.style,
		...colorProps.style,
		...spacingProps.style,
	};

	const wrapperClasses = classnames(
		blockProps.className,
		'wp-block-button',
		{ [ `has-text-align-${ textAlign }` ]: textAlign },
	);

	return (
		<div className={wrapperClasses}>
			<RichText.Content
			  	{...blockProps}
				tagName={ TagName }
				className={ buttonClasses }
				href={ url( attributes.selected_membership_level ) }
				style={ buttonStyle }
				value={ text }
			/>
		</div>
	);
}
