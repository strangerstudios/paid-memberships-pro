/**
 * WordPress dependencies
 */
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

export default function Save(props) {
  const blockProps = useBlockProps.save();
  const getExpirationText = (level) => {
    return pmpro.all_levels_formatted_text[level]
      ? pmpro.all_levels_formatted_text[level].formatted_expiration
      : "";
  };

  return (
    <div {...blockProps}>
      {getExpirationText(props.attributes.selected_level)}
    </div>
  );
}
