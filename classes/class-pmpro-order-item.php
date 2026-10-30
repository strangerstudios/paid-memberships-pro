<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- CRUD for the PMPro order items custom table, which has no WordPress API or object cache layer.

/**
 * An order item (a "line"): one thing that an order bought, such as the membership, a discount code's
 * saving, seats, a donation or a package.
 *
 * Get an order's lines with MemberOrder::get_items(), which also covers orders without saved lines. Use
 * get_order_items() to search lines across orders.
 *
 * Lines describe the order. The order's subtotal, tax and total stay the source of truth for money, so a
 * saved line is never changed. MemberOrder saves the checkout lines (see pmpro_normalize_checkout_item())
 * the first time a checkout order is saved.
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
	 * Get a saved line by ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The line's ID.
	 * @return PMPro_Order_Item|null The line, or null if it doesn't exist.
	 */
	public static function get_order_item( $id ) {
		if ( empty( $id ) ) {
			return null;
		}

		$items = self::get_order_items(
			array(
				'id'    => (int) $id,
				'limit' => 1,
			)
		);
		return empty( $items ) ? null : $items[0];
	}

	/**
	 * Get saved lines, oldest first.
	 *
	 * This only reads saved rows. For an order's lines, use MemberOrder::get_items(), which also covers
	 * orders without saved lines and keeps the membership line in line with the order subtotal.
	 *
	 * @since TBD
	 *
	 * @param array $args {
	 *     Optional. Each filter takes a single value or an array of values.
	 *
	 *     @type int|int[]       $id             Line ID(s).
	 *     @type int|int[]       $order_id       Order ID(s).
	 *     @type int|int[]       $parent_item_id Parent line ID(s). 0 for top-level lines.
	 *     @type string|string[] $item_type      Item type(s), such as 'membership' or 'seats'.
	 *     @type int|int[]       $object_id      Object ID(s). Usually used with item_type.
	 *     @type int             $limit          Maximum number of lines. Default 100. 0 for no limit.
	 *     @type int             $offset         Number of lines to skip. Default 0.
	 * }
	 * @return PMPro_Order_Item[] The lines.
	 */
	public static function get_order_items( $args = array() ) {
		global $wpdb;

		$where    = array();
		$prepared = array();
		$columns  = array(
			'id'             => '%d',
			'order_id'       => '%d',
			'parent_item_id' => '%d',
			'item_type'      => '%s',
			'object_id'      => '%d',
		);
		foreach ( $columns as $column => $format ) {
			if ( ! isset( $args[ $column ] ) ) {
				continue;
			}

			$values = (array) $args[ $column ];
			if ( empty( $values ) ) {
				return array();
			}

			$where[]  = $column . ' IN ( ' . implode( ', ', array_fill( 0, count( $values ), $format ) ) . ' )';
			$prepared = array_merge( $prepared, $values );
		}

		$sql = "SELECT id, order_id, parent_item_id, item_type, object_id, name, quantity, subtotal, billing_amount FROM $wpdb->pmpro_membership_order_items";
		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}
		$sql .= ' ORDER BY id ASC';

		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 100;
		if ( $limit > 0 ) {
			$sql       .= ' LIMIT %d, %d';
			$prepared[] = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
			$prepared[] = $limit;
		}

		$rows = empty( $prepared ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, $prepared ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built from fixed columns and placeholders above.

		return array_map(
			function ( $row ) {
				return new PMPro_Order_Item( $row );
			},
			$rows
		);
	}

	/**
	 * Save a new line.
	 *
	 * Lines are only added, for a checkout order that is being saved for the first time. A saved line is
	 * never changed, so this refuses a line that already has an ID.
	 *
	 * @since TBD
	 *
	 * @return bool True if the line was saved. Its ID is set.
	 */
	public function save() {
		global $wpdb;

		if ( ! empty( $this->id ) || empty( $this->order_id ) ) {
			return false;
		}

		$inserted = $wpdb->insert(
			$wpdb->pmpro_membership_order_items,
			array(
				'order_id'       => $this->order_id,
				'parent_item_id' => $this->parent_item_id,
				'item_type'      => $this->item_type,
				'object_id'      => $this->object_id,
				'name'           => $this->name,
				'quantity'       => $this->quantity,
				'subtotal'       => $this->subtotal,
				'billing_amount' => $this->billing_amount,
			),
			array( '%d', '%d', '%s', '%d', '%s', '%d', '%f', '%f' )
		);
		if ( false === $inserted ) {
			return false;
		}

		$this->id = (int) $wpdb->insert_id;
		return true;
	}

	/**
	 * Delete all of an order's lines, such as when the order is deleted.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 * @return bool True if the query ran.
	 */
	public static function delete_order_items( $order_id ) {
		global $wpdb;

		if ( empty( $order_id ) ) {
			return false;
		}

		return false !== $wpdb->delete( $wpdb->pmpro_membership_order_items, array( 'order_id' => (int) $order_id ), array( '%d' ) );
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
