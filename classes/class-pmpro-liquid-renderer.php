<?php
/**
 * Liquid-style template renderer.
 *
 * Handles {{ variable | filter }} output tags and
 * {% if %} / {% elsif %} / {% else %} / {% endif %} conditionals.
 *
 * @since TBD
 */
class PMPro_Liquid_Renderer {

	/**
	 * Process Liquid-style template syntax in the given content.
	 *
	 * @since TBD
	 *
	 * @param string $content The content to process.
	 * @param array  $data    Key-value pairs for variable resolution.
	 * @return string The processed content.
	 */
	public static function render( $content, $data ) {
		if ( empty( $content ) || ! is_array( $data ) ) {
			return $content;
		}

		// Process conditionals first so we only render the winning branches.
		$content = self::process_conditionals( $content, $data );

		// Then process {{ variable | filter }} output tags.
		$content = self::process_output_tags( $content, $data );

		return $content;
	}

	/**
	 * Get the registry of available Liquid filters.
	 *
	 * Returns a map of filter name => array( 'callback' => callable, 'description' => string ).
	 * The registry is extensible via the 'pmpro_liquid_filters' WordPress filter hook.
	 *
	 * @since TBD
	 *
	 * @return array The filter registry.
	 */
	public static function get_filters() {
		$filters = array(
			'upcase'     => array(
				'callback'    => function ( $value ) {
					return strtoupper( (string) $value );
				},
				'description' => __( 'Convert to uppercase. Example: {{ name | upcase }}', 'paid-memberships-pro' ),
			),
			'downcase'   => array(
				'callback'    => function ( $value ) {
					return strtolower( (string) $value );
				},
				'description' => __( 'Convert to lowercase. Example: {{ name | downcase }}', 'paid-memberships-pro' ),
			),
			'capitalize' => array(
				'callback'    => function ( $value ) {
					return ucwords( strtolower( (string) $value ) );
				},
				'description' => __( 'Capitalize each word. Example: {{ name | capitalize }}', 'paid-memberships-pro' ),
			),
			'strip'      => array(
				'callback'    => function ( $value ) {
					return trim( (string) $value );
				},
				'description' => __( 'Remove leading and trailing whitespace. Example: {{ name | strip }}', 'paid-memberships-pro' ),
			),
			'strip_html' => array(
				'callback'    => function ( $value ) {
					return wp_strip_all_tags( (string) $value );
				},
				'description' => __( 'Remove all HTML tags. Example: {{ billing_address | strip_html }}', 'paid-memberships-pro' ),
			),
			'escape'     => array(
				'callback'    => function ( $value ) {
					return esc_html( (string) $value );
				},
				'description' => __( 'Escape HTML entities. Example: {{ name | escape }}', 'paid-memberships-pro' ),
			),
			'default'    => array(
				'callback'    => function ( $value, $default_value = '' ) {
					return ( $value === null || $value === '' || $value === false ) ? $default_value : $value;
				},
				'description' => __( 'Use a default value if empty. Example: {{ discount_code | default: "None" }}', 'paid-memberships-pro' ),
			),
			'date'       => array(
				'callback'    => function ( $value, $format = '' ) {
					if ( empty( $format ) || empty( $value ) ) {
						return (string) $value;
					}
					$timestamp = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
					if ( $timestamp === false ) {
						return (string) $value;
					}
					return date_i18n( $format, $timestamp );
				},
				'description' => __( 'Format a date value using PHP date format. Example: {{ order_date | date: "F j, Y" }}', 'paid-memberships-pro' ),
			),
			'size'       => array(
				'callback'    => function ( $value ) {
					if ( is_array( $value ) ) {
						return count( $value );
					}
					return strlen( (string) $value );
				},
				'description' => __( 'Return the length of a string. Example: {{ name | size }}', 'paid-memberships-pro' ),
			),
		);

		/**
		 * Filter the available Liquid template filters.
		 *
		 * Allows developers to add custom Liquid filters for email templates.
		 * Filter entries may be either callable values or arrays containing
		 * 'callback' (callable) and optional 'description' (string) keys.
		 *
		 * @since TBD
		 *
		 * @param array $filters The filter registry.
		 */
		$filters = apply_filters( 'pmpro_liquid_filters', $filters );

		return self::normalize_filter_registry( $filters );
	}

	/**
	 * Normalize the filter registry to a consistent array structure.
	 *
	 * @since TBD
	 *
	 * @param mixed $filters The potentially customized filter registry.
	 * @return array Normalized registry keyed by filter name.
	 */
	private static function normalize_filter_registry( $filters ) {
		if ( ! is_array( $filters ) ) {
			return array();
		}

		$normalized_filters = array();

		foreach ( $filters as $filter_name => $filter_definition ) {
			if ( is_callable( $filter_definition ) ) {
				$normalized_filters[ $filter_name ] = array(
					'callback'    => $filter_definition,
					'description' => '',
				);
				continue;
			}

			if ( ! is_array( $filter_definition ) || empty( $filter_definition['callback'] ) || ! is_callable( $filter_definition['callback'] ) ) {
				continue;
			}

			$normalized_filters[ $filter_name ] = array(
				'callback'    => $filter_definition['callback'],
				'description' => isset( $filter_definition['description'] ) ? (string) $filter_definition['description'] : '',
			);
		}

		return $normalized_filters;
	}

	/**
	 * Process all {% if %} ... {% endif %} conditional blocks in the content.
	 *
	 * Finds outermost conditional blocks first, evaluates them,
	 * and recursively processes nested conditionals in the winning segment.
	 *
	 * @since TBD
	 *
	 * @param string $content The content containing conditional blocks.
	 * @param array  $data    Key-value pairs for variable resolution.
	 * @return string The content with all conditional blocks resolved.
	 */
	private static function process_conditionals( $content, $data ) {
		// Keep processing until no more outermost {% if %} blocks are found.
		while ( true ) {
			// Find the first {% if ... %} tag.
			if ( ! preg_match( '/\{%\s*if\s+(.+?)\s*%\}/s', $content, $match, PREG_OFFSET_CAPTURE ) ) {
				break;
			}

			$block_start = $match[0][1];
			$condition   = $match[1][0];
			$search_from = $block_start + strlen( $match[0][0] );

			// Track nesting depth to find the matching {% endif %}.
			$depth   = 1;
			$pos     = $search_from;
			$found   = false;

			while ( $depth > 0 && preg_match( '/\{%\s*(if\s+|endif)\s*.*?%\}/s', $content, $tag_match, PREG_OFFSET_CAPTURE, $pos ) ) {
				$tag_text = $tag_match[0][0];
				$tag_pos  = $tag_match[0][1];
				$tag_type = trim( $tag_match[1][0] );

				if ( strpos( $tag_type, 'if' ) === 0 ) {
					$depth++;
				} else {
					$depth--;
				}

				if ( $depth === 0 ) {
					// Found matching {% endif %}.
					$block_end   = $tag_pos + strlen( $tag_text );
					$block_inner = substr( $content, $search_from, $tag_pos - $search_from );
					$replacement = self::evaluate_conditional_block( $condition, $block_inner, $data );
					$content     = substr( $content, 0, $block_start ) . $replacement . substr( $content, $block_end );
					$found       = true;
					break;
				}

				$pos = $tag_pos + strlen( $tag_text );
			}

			// If no matching {% endif %} was found, stop to avoid infinite loop.
			if ( ! $found ) {
				break;
			}
		}

		return $content;
	}

	/**
	 * Evaluate a conditional block and return the content of the winning branch.
	 *
	 * @since TBD
	 *
	 * @param string $condition   The condition expression from the {% if %} tag.
	 * @param string $block_inner The content between {% if %} and {% endif %}.
	 * @param array  $data        Key-value pairs for variable resolution.
	 * @return string The content of the first branch whose condition is truthy.
	 */
	private static function evaluate_conditional_block( $condition, $block_inner, $data ) {
		// Split block_inner into branches at top-level {% elsif %} and {% else %} tags.
		$branches = array();
		$depth    = 0;
		$current  = '';
		$pos      = 0;

		while ( $pos < strlen( $block_inner ) ) {
			// Check for nested {% if %} or {% endif %} to track depth.
			if ( preg_match( '/\{%\s*(if\s+|endif)\s*.*?%\}/s', $block_inner, $tag_match, PREG_OFFSET_CAPTURE, $pos ) && $tag_match[0][1] === $pos ) {
				$tag_type = trim( $tag_match[1][0] );
				if ( strpos( $tag_type, 'if' ) === 0 ) {
					$depth++;
				} else {
					$depth--;
				}
				$current .= $tag_match[0][0];
				$pos      = $tag_match[0][1] + strlen( $tag_match[0][0] );
				continue;
			}

			// At top level, check for {% elsif %} or {% else %}.
			if ( $depth === 0 ) {
				if ( preg_match( '/\{%\s*elsif\s+(.+?)\s*%\}/s', $block_inner, $elsif_match, PREG_OFFSET_CAPTURE, $pos ) && $elsif_match[0][1] === $pos ) {
					$branches[] = array( 'condition' => $condition, 'content' => $current );
					$condition  = $elsif_match[1][0];
					$current    = '';
					$pos        = $elsif_match[0][1] + strlen( $elsif_match[0][0] );
					continue;
				}

				if ( preg_match( '/\{%\s*else\s*%\}/s', $block_inner, $else_match, PREG_OFFSET_CAPTURE, $pos ) && $else_match[0][1] === $pos ) {
					$branches[] = array( 'condition' => $condition, 'content' => $current );
					$condition  = '__else__';
					$current    = '';
					$pos        = $else_match[0][1] + strlen( $else_match[0][0] );
					continue;
				}
			}

			$current .= $block_inner[ $pos ];
			$pos++;
		}

		// Add the final branch.
		$branches[] = array( 'condition' => $condition, 'content' => $current );

		// Evaluate each branch and return the first truthy one.
		foreach ( $branches as $branch ) {
			$is_match = ( $branch['condition'] === '__else__' ) || self::evaluate_condition( $branch['condition'], $data );
			if ( $is_match ) {
				// Recursively process nested conditionals in the winning branch.
				return self::process_conditionals( $branch['content'], $data );
			}
		}

		return '';
	}

	/**
	 * Evaluate a condition expression string and return a boolean result.
	 *
	 * Supports truthy checks, comparison operators (==, !=, >, <, >=, <=),
	 * empty checks, and boolean operators (and, or).
	 *
	 * @since TBD
	 *
	 * @param string $condition_string The condition expression to evaluate.
	 * @param array  $data             Key-value pairs for variable resolution.
	 * @return bool The result of the condition evaluation.
	 */
	private static function evaluate_condition( $condition_string, $data ) {
		$condition_string = trim( $condition_string );

		// Evaluate top-level boolean operators left-to-right, ignoring quoted strings.
		$boolean_chain = self::parse_boolean_condition_chain( $condition_string );
		if ( ! empty( $boolean_chain ) ) {
			$result = self::evaluate_condition( $boolean_chain['operands'][0], $data );

			foreach ( $boolean_chain['operators'] as $index => $operator ) {
				if ( $operator === 'and' ) {
					if ( ! $result ) {
						continue;
					}
					$next_result = self::evaluate_condition( $boolean_chain['operands'][ $index + 1 ], $data );
					$result = $result && $next_result;
				} else {
					if ( $result ) {
						continue;
					}
					$next_result = self::evaluate_condition( $boolean_chain['operands'][ $index + 1 ], $data );
					$result = $result || $next_result;
				}
			}

			return $result;
		}

		return self::evaluate_single_condition( $condition_string, $data );
	}

	/**
	 * Evaluate a single condition without top-level boolean chaining.
	 *
	 * @since TBD
	 *
	 * @param string $condition_string The condition expression to evaluate.
	 * @param array  $data             Key-value pairs for variable resolution.
	 * @return bool The result of the condition evaluation.
	 */
	private static function evaluate_single_condition( $condition_string, $data ) {
		// Check for comparison operators.
		$comparison = self::parse_comparison_condition( $condition_string );
		if ( ! empty( $comparison ) ) {
			$left_token  = $comparison['left'];
			$operator    = $comparison['operator'];
			$right_token = $comparison['right'];

			$left_value = self::resolve_value( $left_token, $data );

			// Handle "empty" keyword on the right side.
			if ( $right_token === 'empty' ) {
				$is_empty = ( $left_value === null || $left_value === '' || $left_value === false || ( is_array( $left_value ) && empty( $left_value ) ) );
				return ( $operator === '==' ) ? $is_empty : ! $is_empty;
			}

			$right_value = self::resolve_value( $right_token, $data );

			switch ( $operator ) {
				case '==':
					return $left_value == $right_value;
				case '!=':
					return $left_value != $right_value;
				case '>':
					return $left_value > $right_value;
				case '<':
					return $left_value < $right_value;
				case '>=':
					return $left_value >= $right_value;
				case '<=':
					return $left_value <= $right_value;
			}
		}

		// Simple truthy check.
		$value = self::resolve_value( $condition_string, $data );
		return ! empty( $value );
	}

	/**
	 * Parse a single comparison condition, respecting quoted strings.
	 *
	 * @since TBD
	 *
	 * @param string $condition_string The condition expression to parse.
	 * @return array|null {
	 *     Parsed comparison condition.
	 *
	 *     @type string $left     Left-hand token.
	 *     @type string $operator Comparison operator.
	 *     @type string $right    Right-hand token.
	 * }
	 */
	private static function parse_comparison_condition( $condition_string ) {
		$current_quote     = '';
		$parentheses_depth = 0;
		$length            = strlen( $condition_string );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $condition_string[ $i ];

			if ( $current_quote !== '' ) {
				if ( $char === $current_quote && ! self::is_escaped_character( $condition_string, $i ) ) {
					$current_quote = '';
				}
				continue;
			}

			if ( $char === '"' || $char === "'" ) {
				$current_quote = $char;
				continue;
			}

			if ( $char === '(' ) {
				$parentheses_depth++;
				continue;
			}

			if ( $char === ')' && $parentheses_depth > 0 ) {
				$parentheses_depth--;
				continue;
			}

			if ( $parentheses_depth > 0 ) {
				continue;
			}

			$operator        = null;
			$operator_length = 0;

			$two_char_operator = substr( $condition_string, $i, 2 );
			if ( in_array( $two_char_operator, array( '==', '!=', '>=', '<=' ), true ) ) {
				$operator        = $two_char_operator;
				$operator_length = 2;
			} elseif ( $char === '>' || $char === '<' ) {
				$operator        = $char;
				$operator_length = 1;
			}

			if ( $operator !== null ) {
				$left_token  = trim( substr( $condition_string, 0, $i ) );
				$right_token = trim( substr( $condition_string, $i + $operator_length ) );

				if ( $left_token === '' || $right_token === '' ) {
					return null;
				}

				return array(
					'left'     => $left_token,
					'operator' => $operator,
					'right'    => $right_token,
				);
			}
		}

		return null;
	}

	/**
	 * Parse a condition into top-level boolean operands/operators.
	 *
	 * Returns null when no top-level boolean chain is present.
	 *
	 * @since TBD
	 *
	 * @param string $condition_string The condition expression to parse.
	 * @return array|null {
	 *     Parsed boolean chain.
	 *
	 *     @type array $operands  The condition operands.
	 *     @type array $operators The boolean operators between operands.
	 * }
	 */
	private static function parse_boolean_condition_chain( $condition_string ) {
		$operands          = array();
		$operators         = array();
		$current_operand   = '';
		$current_quote     = '';
		$parentheses_depth = 0;
		$length            = strlen( $condition_string );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $condition_string[ $i ];

			if ( $current_quote !== '' ) {
				$current_operand .= $char;
				if ( $char === $current_quote && ! self::is_escaped_character( $condition_string, $i ) ) {
					$current_quote = '';
				}
				continue;
			}

			if ( $char === '"' || $char === "'" ) {
				$current_quote   = $char;
				$current_operand .= $char;
				continue;
			}

			if ( $char === '(' ) {
				$parentheses_depth++;
				$current_operand .= $char;
				continue;
			}

			if ( $char === ')' && $parentheses_depth > 0 ) {
				$parentheses_depth--;
				$current_operand .= $char;
				continue;
			}

			if ( $parentheses_depth === 0 ) {
				$operator = self::get_boolean_operator_at_offset( $condition_string, $i );
				if ( $operator !== null ) {
					$operands[]      = trim( $current_operand );
					$operators[]     = $operator;
					$current_operand = '';
					$i              += strlen( $operator ) - 1;
					continue;
				}
			}

			$current_operand .= $char;
		}

		if ( empty( $operators ) ) {
			return null;
		}

		$operands[] = trim( $current_operand );

		// Ignore malformed expressions (e.g., leading/trailing operator).
		foreach ( $operands as $operand ) {
			if ( $operand === '' ) {
				return null;
			}
		}

		return array(
			'operands'  => $operands,
			'operators' => $operators,
		);
	}

	/**
	 * Detect a boolean operator at a specific offset.
	 *
	 * @since TBD
	 *
	 * @param string $condition_string The condition expression.
	 * @param int    $offset           The character offset to inspect.
	 * @return string|null The boolean operator ('and' or 'or') or null.
	 */
	private static function get_boolean_operator_at_offset( $condition_string, $offset ) {
		foreach ( array( 'and', 'or' ) as $operator ) {
			$operator_length = strlen( $operator );
			if ( substr( $condition_string, $offset, $operator_length ) !== $operator ) {
				continue;
			}

			$before_char = ( $offset > 0 ) ? $condition_string[ $offset - 1 ] : '';
			$after_index = $offset + $operator_length;
			$after_char  = ( $after_index < strlen( $condition_string ) ) ? $condition_string[ $after_index ] : '';

			$before_is_boundary = ( $before_char === '' ) || ! preg_match( '/[A-Za-z0-9_]/', $before_char );
			$after_is_boundary  = ( $after_char === '' ) || ! preg_match( '/[A-Za-z0-9_]/', $after_char );

			if ( $before_is_boundary && $after_is_boundary ) {
				return $operator;
			}
		}

		return null;
	}

	/**
	 * Determine whether a character at a given offset is escaped.
	 *
	 * @since TBD
	 *
	 * @param string $string The source string.
	 * @param int    $offset The offset to inspect.
	 * @return bool True if escaped; false otherwise.
	 */
	private static function is_escaped_character( $string, $offset ) {
		if ( $offset <= 0 ) {
			return false;
		}

		$backslashes = 0;
		for ( $i = $offset - 1; $i >= 0 && $string[ $i ] === '\\'; $i-- ) {
			$backslashes++;
		}

		return ( $backslashes % 2 ) === 1;
	}

	/**
	 * Resolve a value token to its actual value.
	 *
	 * @since TBD
	 *
	 * @param string $token The token to resolve.
	 * @param array  $data  Key-value pairs for variable resolution.
	 * @return mixed The resolved value.
	 */
	private static function resolve_value( $token, $data ) {
		$token = trim( $token );

		// Quoted strings.
		if ( preg_match( '/^([\'"])(.*)\\1$/s', $token, $quote_match ) ) {
			return $quote_match[2];
		}

		// Boolean literals.
		if ( $token === 'true' ) {
			return true;
		}
		if ( $token === 'false' ) {
			return false;
		}

		// Null literals.
		if ( $token === 'empty' || $token === 'nil' || $token === 'null' ) {
			return null;
		}

		// Numeric literals.
		if ( is_numeric( $token ) ) {
			return $token + 0;
		}

		// Variable lookup in $data.
		if ( isset( $data[ $token ] ) ) {
			return $data[ $token ];
		}

		return null;
	}

	/**
	 * Process all {{ variable }} and {{ variable | filter }} output tags.
	 *
	 * @since TBD
	 *
	 * @param string $content The content containing output tags.
	 * @param array  $data    Key-value pairs for variable resolution.
	 * @return string The content with all output tags resolved.
	 */
	private static function process_output_tags( $content, $data ) {
		return preg_replace_callback(
			'/\{\{(.+?)\}\}/s',
			function ( $matches ) use ( $data ) {
				$expression = $matches[1];
				$parts      = self::split_filter_expression( $expression );

				// First part is the variable name.
				$var_name = trim( $parts[0] );
				$value    = self::resolve_value( $var_name, $data );

				// Remaining parts are filters.
				for ( $i = 1; $i < count( $parts ); $i++ ) {
					$value = self::apply_filter( $value, $parts[ $i ], $data );
				}

				return ( $value === null ) ? '' : (string) $value;
			},
			$content
		);
	}

	/**
	 * Split an output expression by filter pipes, respecting quoted strings.
	 *
	 * @since TBD
	 *
	 * @param string $expression The expression inside {{ ... }}.
	 * @return array The split expression parts.
	 */
	private static function split_filter_expression( $expression ) {
		$parts         = array();
		$current_part  = '';
		$current_quote = '';
		$length        = strlen( $expression );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $expression[ $i ];

			if ( $current_quote !== '' ) {
				$current_part .= $char;
				if ( $char === $current_quote && ! self::is_escaped_character( $expression, $i ) ) {
					$current_quote = '';
				}
				continue;
			}

			if ( $char === '"' || $char === "'" ) {
				$current_quote = $char;
				$current_part .= $char;
				continue;
			}

			if ( $char === '|' ) {
				$parts[]      = $current_part;
				$current_part = '';
				continue;
			}

			$current_part .= $char;
		}

		$parts[] = $current_part;

		return $parts;
	}

	/**
	 * Apply a single filter to a value.
	 *
	 * @since TBD
	 *
	 * @param mixed  $value             The current value.
	 * @param string $filter_expression The filter expression (e.g., "upcase" or "default: 'N/A'").
	 * @param array  $data              Key-value pairs for variable resolution in filter args.
	 * @return mixed The filtered value.
	 */
	private static function apply_filter( $value, $filter_expression, $data ) {
		$filter_expression = trim( $filter_expression );

		// Split on first colon to separate filter name from arguments.
		$parts       = explode( ':', $filter_expression, 2 );
		$filter_name = trim( $parts[0] );
		$args        = array();

		if ( isset( $parts[1] ) ) {
			$args = self::parse_filter_args( $parts[1], $data );
		}

		$filters = self::get_filters();

		if ( isset( $filters[ $filter_name ]['callback'] ) && is_callable( $filters[ $filter_name ]['callback'] ) ) {
			return call_user_func( $filters[ $filter_name ]['callback'], $value, ...$args );
		}

		// Unknown filter: return value unchanged.
		return $value;
	}

	/**
	 * Parse filter arguments from a colon-separated argument string.
	 *
	 * @since TBD
	 *
	 * @param string $args_string The arguments string (everything after the colon).
	 * @param array  $data        Key-value pairs for variable resolution.
	 * @return array The parsed arguments.
	 */
	private static function parse_filter_args( $args_string, $data ) {
		$args_string = trim( $args_string );
		if ( $args_string === '' ) {
			return array();
		}

		// Split on commas, respecting quoted strings.
		preg_match_all( "/(?:'[^']*'|\"[^\"]*\"|[^,])+/", $args_string, $matches );

		$args = array();
		foreach ( $matches[0] as $arg ) {
			$arg = trim( $arg );

			// Strip surrounding quotes.
			if ( preg_match( '/^([\'"])(.*)\\1$/s', $arg, $quote_match ) ) {
				$args[] = $quote_match[2];
			} elseif ( is_numeric( $arg ) ) {
				$args[] = $arg + 0;
			} else {
				// Resolve as a variable reference.
				$args[] = self::resolve_value( $arg, $data );
			}
		}

		return $args;
	}
}
