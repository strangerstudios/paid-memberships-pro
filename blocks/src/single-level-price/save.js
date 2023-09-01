/**
 * WordPress dependencies
 */
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

export default function Save(props) {
  const blockProps = useBlockProps.save();
  const getFormattedPrice = (level) => {
    return pmpro.all_levels_formatted_text[level]
      ? pmpro.all_levels_formatted_text[level].formatted_price
      : "";
  };

  return (
    <div {...blockProps}>
      {getFormattedPrice(props.attributes.selected_level)}
    </div>
  );
}
