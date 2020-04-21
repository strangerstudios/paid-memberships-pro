/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { Component } = wp.element;
const { PanelBody, SelectControl, ToggleControl } = wp.components;
const { InspectorControls } = wp.editor;

/**
 * Create an Inspector Controls wrapper Component
 */
export default class Inspector extends Component {
	constructor() {
		super(...arguments);
	}

	render() {
		const { attributes, setAttributes } = this.props;
		const {
			display_if_logged_in,
			show_menu,
			show_logout_link,
			location,
		} = attributes;
		const locations = [
			{
				value: "shortcode",
				label: __("Shortcode", "post-type-archive-mapping"),
			},
			{ value: "widget", label: __("Widget", "post-type-archive-mapping") },
		];
		return (
			<InspectorControls>
				<PanelBody>
					<ToggleControl
						label={__("Display If Logged In", "post-type-archive-mapping")}
						checked={display_if_logged_in}
						onChange={(value) => {
							this.props.setAttributes({
								display_if_logged_in: value,
							});
						}}
					/>
					<ToggleControl
						label={__("Show Menu", "post-type-archive-mapping")}
						checked={show_menu}
						onChange={(value) => {
							this.props.setAttributes({
								show_menu: value,
							});
						}}
					/>
					<ToggleControl
						label={__("Show Logout Link", "post-type-archive-mapping")}
						checked={show_logout_link}
						onChange={(value) => {
							this.props.setAttributes({
								show_logout_link: value,
							});
						}}
					/>
					<SelectControl
						label={__("Location", "paid-memberships-pro")}
						value={location}
						onChange={(location) => setAttributes({ location })}
						options={locations}
					/>
				</PanelBody>
			</InspectorControls>
		);
	}
}
