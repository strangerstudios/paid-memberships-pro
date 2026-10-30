<?php
/**
 * An order item (a "line"): one thing that an order bought, such as the membership, a discount code's
 * saving, seats, a donation or a package.
 *
 * Get an order's lines with MemberOrder::get_items(). Lines describe the order. The order's subtotal, tax
 * and total stay the source of truth for money. Lines are only saved by MemberOrder, from the checkout
 * lines (see pmpro_normalize_checkout_item()) the first time a checkout order is saved.
 *
 * @since TBD
 */
class PMPro_Order_Item {

	/**
	 * The line's ID. 0 for a line built from an order without saved lines.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * The ID of the order this line belongs to.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $order_id = 0;

	/**
	 * The ID of the saved line this line sits under, or 0 for a top-level line.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $parent_item_id = 0;

	/**
	 * What the line is, such as 'seats' or 'donation'. 'membership' and 'discount' are core only.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $item_type = '';

	/**
	 * The level ID for the membership line and the discount code ID for the discount line. Defined by the Add On otherwise.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $object_id = 0;

	/**
	 * The name shown at checkout, on the invoice and in emails.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The quantity. At least 1.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $quantity = 1;

	/**
	 * The amount charged on this order for the whole quantity, before tax.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	public $subtotal = 0;

	/**
	 * The amount added to each recurring payment. 0 for a one-time line.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	public $billing_amount = 0;

	/**
	 * Create a line from a saved row, or from an array or object with any of the properties above.
	 *
	 * @since TBD
	 *
	 * @param array|object $data The line's properties.
	 */
	public function __construct( $data = array() ) {
		$data = (array) $data;

		$this->id             = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$this->order_id       = isset( $data['order_id'] ) ? (int) $data['order_id'] : 0;
		$this->parent_item_id = isset( $data['parent_item_id'] ) ? (int) $data['parent_item_id'] : 0;
		$this->item_type      = isset( $data['item_type'] ) ? sanitize_key( $data['item_type'] ) : '';
		$this->object_id      = isset( $data['object_id'] ) ? (int) $data['object_id'] : 0;
		$this->name           = isset( $data['name'] ) ? mb_substr( sanitize_text_field( $data['name'] ), 0, 255 ) : ''; // The name column is varchar(255).
		$this->quantity       = isset( $data['quantity'] ) ? max( 1, (int) $data['quantity'] ) : 1;
		$this->subtotal       = isset( $data['subtotal'] ) ? pmpro_round_price( (float) $data['subtotal'] ) : 0;
		$this->billing_amount = isset( $data['billing_amount'] ) ? pmpro_round_price( (float) $data['billing_amount'] ) : 0;
	}

	/**
	 * Get the line's label, such as "Seats" or "3 × Seats".
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_label() {
		if ( $this->quantity > 1 ) {
			/* translators: 1: quantity, 2: item name. */
			return sprintf( __( '%1$d × %2$s', 'paid-memberships-pro' ), $this->quantity, $this->name );
		}
		return $this->name;
	}
}
