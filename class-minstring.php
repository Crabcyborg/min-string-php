<?php

/**
 * A small collection methods that work together to shrink an array of integer values.
 */
class MinString {

	/**
	 * Symbols used for base 64 compression.
	 *
	 * @var string $base_64_symbols
	 */
	private $base_64_symbols = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!$';

	/**
	 * Symbols used for the counter compression function.
	 *
	 * @var string $counter_symbols
	 */
	private $counter_symbols = '^*-_~`';

	/**
	 * Symbols used for the three character permutation compression function.
	 *
	 * @var string $three_character_permutations_symbols
	 */
	private $three_character_permutations_symbols = '<>()[]';

	/**
	 * Symbols used for the two character permutation comrpession function.
	 *
	 * @var string $two_character_permutations_symbols
	 */
	private $two_character_permutations_symbols = '{}+|';

	/**
	 * Symbols used for other compression functions (stored in one string for reference).
	 *
	 * @var string $additional_symbols
	 */
	private $additional_symbols = '@=\'";:';

	/**
	 * The active string value from start to finish.
	 *
	 * @var string $string
	 */
	private $string;

	/**
	 * MinString constructor method.
	 *
	 * @param string $string the raw csv string.
	 */
	public function __construct( $string ) {
		$this->string = $string;
	}

	/**
	 * Get the current string with all of its applied filters
	 *
	 * @return string
	 */
	public function get_active_string() {
		return $this->string;
	}

	/**
	 * Convert integer array values to base 64 data.
	 */
	public function to_base_64() {
		$exploded = explode( ',', $this->string );
		$symbols  = $this->base_64_symbols;
		$length   = count( $exploded );

		$output = array();
		for ( $i = 0; $i < $length; $i += 3 ) {
			$set   = array_slice( $exploded, $i, 3 );
			$count = count( $set );
			while ( $count < 3 ) {
				$set[] = 0;
				++$count;
			}

			$quotient = $set[0] + ( $set[1] << 8 ) + ( $set[2] << 16 );
			$result   = '';

			while ( $quotient ) {
				$remainder = $quotient % 64;
				$quotient  = floor( $quotient / 64 );
				$result    = $symbols[ $remainder ] . $result;
			}

			$output[] = str_pad( $result, 4, '0', STR_PAD_LEFT );
		}

		$this->string = implode( '', $output );
	}

	/**
	 * Counter repeat instances of characters and reduce
	 */
	public function counter() {
		$symbols    = $this->counter_symbols;
		$previous   = false;
		$count      = 0;
		$output     = '';
		$length     = strlen( $symbols );
		$last_index = $length - 1;
		$characters = str_split( $this->string );

		foreach ( $characters as $c ) {
			if ( $c === $previous ) {
				++ $count;

				if ( $count - 3 === $length ) {
					$output .= $previous;
					$output .= $symbols[ $last_index ];
					$count   = 1;
				}
			} else {
				$output  .= $this->add_previous_character_to_output( $previous, $count, $symbols );
				$count    = 1;
				$previous = $c;
			}
		}

		$output      .= $this->add_previous_character_to_output( $previous, $count, $symbols );
		$this->string = $output;
	}

	/**
	 * Add previous character to output.
	 *
	 * @param string $previous the previous character value.
	 * @param int    $count the repeated count fount for the previous character value.
	 * @param string $symbols the counter symbols.
	 * @return string
	 */
	private function add_previous_character_to_output( $previous, $count, $symbols ) {
		$output = '';
		if ( false !== $previous ) {
			$output .= $previous;
			if ( $count > 1 ) {
				$output .= 2 === $count ? $previous : $symbols[ $count - 3 ];
			}
		}
		return $output;
	}

	/**
	 * Reduce the two most common patterns to single characters
	 */
	public function two_most_common_patterns() {
		$this->string = str_replace( array( '00', '$$' ), array( '@', '=' ), $this->string );
	}

	/**
	 * Replace the third most common pattern
	 */
	public function third_most_common_pattern() {
		$this->string = str_replace( '0^', "'", $this->string );
	}

	/**
	 * Replace common three character patterns
	 */
	public function common_three_character_patterns() {
		$patterns = $this->get_three_character_patterns();
		$symbols  = $this->base_64_symbols;
		$input    = $this->string;
		$length   = count( $patterns );

		for ( $index = 0; $index < $length; ++ $index ) {
			$input = str_replace( $patterns[ $index ], '"' . $symbols[ $index ], $input );
		}

		$this->string = $input;
	}

	/**
	 * Get all common three character patterns that we're going to replace.
	 *
	 * @return array
	 */
	private function get_three_character_patterns() {
		return array( '3$0', 'Y0$', 'M0*', '!f$', '@20', '080', '0Y0', '0f0', '$`$', '3w0', 'fYf', '0fU', '"23', 'c01', 'Y07', '0fY', '!3$', '020', '3M3', 'Y@f', '0fM', '$"3', '$^Y', '640', '030', '1Y0', '1M0', 'Yf=', '@3w', '0c0', '"22', '0M0', '$3$', '!$^', '3$v', '0g0', 'o\'o', 'M3"', '"1M', 'f$^', 'M3M', '0s0', '0v0', '@80', '$*"', '03"' );
	}

	/**
	 * Replace common special patterns
	 */
	public function common_special_patterns() {
		$patterns_length       = count( $this->get_three_character_patterns() );
		$other_patterns        = $this->get_other_patterns();
		$other_patterns_length = count( $other_patterns );
		$input                 = $this->string;
		$input_length          = strlen( $input );

		for ( $index = 0; $index < $other_patterns_length; ++ $index ) {
			$pattern        = $other_patterns[ $index ];
			$pattern_length = strlen( $pattern );
			$length         = $input_length - $pattern_length + 1;

			for ( $i = 0; $i < $length; ++ $i ) {
				$match = true;
				for ( $j = 0; $j < $pattern_length; ++ $j ) {
					if ( ' ' !== $pattern[ $j ] && $input[ $i + $j ] !== $pattern[ $j ] ) {
						$match = false;
						break;
					}
				}

				if ( ! $match ) {
					continue;
				}

				$character_index = $patterns_length + $index;
				$character       = $this->char_at( $character_index );
				$subbed          = '"' . $character;
				for ( $char_index = 0; $char_index < $pattern_length; ++ $char_index ) {
					if ( ' ' === $pattern[ $char_index ] ) {
						$subbed .= $input[ $i + $char_index ];
					}
				}

				$input = $this->replace_at( $input, $i, $subbed, $pattern_length );
			}
		}

		$this->string = $input;
	}

	/**
	 * Find the character to use for the specified $index based off of all available symbols.
	 *
	 * @param int $index where to look for the character at.
	 * @return string character at index.
	 */
	private function char_at( $index ) {
		$base_64_symbols    = $this->base_64_symbols;
		$counter_symbols    = $this->counter_symbols;
		$additional_symbols = $this->additional_symbols;

		if ( $index < strlen( $base_64_symbols ) ) {
			return $base_64_symbols[ $index ];
		}

		$index -= strlen( $base_64_symbols );
		if ( $index < strlen( $counter_symbols ) ) {
			return $counter_symbols[ $index ];
		}

		$index -= strlen( $counter_symbols );
		if ( $index < strlen( $additional_symbols ) ) {
			return $additional_symbols[ $index ];
		}
	}

	/**
	 * Special replace function used to build common_special_patterns string.
	 *
	 * @param string    $target the original string.
	 * @param int       $index the index we're replacing at.
	 * @param string    $replacement the string we're putting in its place.
	 * @param int|false $remove the number of characters to remove.
	 * @return string
	 */
	private function replace_at( $target, $index, $replacement, $remove = false ) {
		if ( ! $remove ) {
			$remove = strlen( $replacement );
		}
		return substr( $target, 0, $index ) . $replacement . substr( $target, $index + $remove );
	}

	/**
	 * Get common irregular patterns
	 *
	 * @return array
	 */
	private function get_other_patterns() {
		return array( '$ $ $', '"  "  "', '$M  $', '01  0', '= $  $', '01  \'', 'M f  0', '0 "  0', '0 "  "', '0  "  0', 'M " M', '3 0 "', 'M  0  "', 'm  q  G', '$  $  f', 'Y $ Y', '"  f  "', '1w0', '0  " "', '$^ $', '1 "V', '1" Y', '" " M', '$M   $', '0  "  "', '" fw' );
	}

	/**
	 * Replace the top two patterns
	 */
	public function top_two_patterns() {
		$this->sub_top_pattern( ';' );
		$this->sub_top_pattern( ':' );
	}

	/**
	 * Determine the most frequent appearing pattern in the active string and replace it with the specified $character.
	 *
	 * @param string $character the character we're replacing the top pattern with.
	 */
	public function sub_top_pattern( $character ) {
		$counts = array();
		$input  = $this->string;
		$to     = strlen( $input ) - 1;

		for ( $i = 0; $i < $to; ++ $i ) {
			$pattern            = $input[ $i ] . $input[ $i + 1 ];
			$counts[ $pattern ] = ( isset( $counts[ $pattern ] ) ? $counts[ $pattern ] : 0 ) + 1;
		}

		$top = $this->top_pattern( $counts );
		if ( $counts[ $top ] <= 2 ) {
			return;
		}

		$first_index  = strpos( $input, $top );
		$this->string = substr( $input, 0, $first_index ) . $character . $top . str_replace( $top, $character, substr( $input, $first_index + 2 ) );
	}

	/**
	 * Determine from a list of counts by array, which pattern appears the most frequently.
	 *
	 * @param array $counts the full array of counts by pattern index.
	 * @return string the pattern with the highest count.
	 */
	private function top_pattern( $counts ) {
		$top = false;

		foreach ( $counts as $key => $count ) {
			if ( ! $top || $count > $counts[ $top ] ) {
				$top = $key;
			}
		}

		return "$top";
	}

	/**
	 * Replace three character permutations
	 */
	public function three_character_permutations() {
		$symbols = $this->three_character_permutations_symbols;
		$input   = $this->string;
		$to      = strlen( $input ) - 2;

		$counts = array();
		for ( $i = 0; $i < $to; ++ $i ) {
			$pattern            = substr( $input, $i, 3 );
			$counts[ $pattern ] = $this->pattern_matches( $pattern, $input )['number'];
		}

		$top = $this->top_pattern( $counts );
		if ( $counts[ $top ] <= 2 ) {
			return;
		}

		$first_index = strpos( $input, $top );
		$matches     = $this->pattern_matches( $top, $input )['matches'];
		$result      = substr( $input, 0, $first_index ) . $symbols[0] . $top;
		$remaining   = substr( $input, $first_index + 3 );
		foreach ( $matches as $rule_index ) {
			$comparison = $this->rule_adjustment( $top, $rule_index );
			$remaining  = str_replace( $comparison, $symbols[ $rule_index ], $remaining );
		}

		$result .= $remaining;

		if ( strlen( $result ) < strlen( $input ) ) {
			$this->string = $result;
		}
	}

	/**
	 * Check a string for any pattern permutation matches.
	 *
	 * @param string $pattern the base pattern we're checking for.
	 * @param string $input the input we're checking the pattern for.
	 * @return array
	 */
	private function pattern_matches( $pattern, $input ) {
		$permutations = $this->permutations( $pattern );
		$to           = strlen( $input ) - 2;

		$matches = array();
		$number  = 0;

		for ( $rule_index = 0; $rule_index < 6; ++ $rule_index ) {
			$permutation = $permutations[ $rule_index ];

			for ( $i = 0; $i < $to; ++ $i ) {
				if ( $input[ $i ] === $permutation[0] && $input[ $i + 1 ] === $permutation[1] && $input[ $i + 2 ] === $permutation[2] ) {
					$matches[ $rule_index ] = $rule_index;
					++ $number;
				}
			}
		}

		return array(
			'matches' => array_values( $matches ),
			'number'  => $number,
		);
	}

	/**
	 * Adjust pattern based off of rule.
	 *
	 * @param string $pattern the original pattern.
	 * @param int    $rule_index the index of the rule we're applying.
	 * @return string
	 */
	private function rule_adjustment( $pattern, $rule_index ) {
		$pattern = "{$pattern}";
		switch ( $rule_index ) {
			case 0:
				return $pattern;                                // < do nothing.
			case 1:
				return $pattern[1] . $pattern[2] . $pattern[0]; // > shift left 1.
			case 2:
				return $pattern[2] . $pattern[0] . $pattern[1]; // ( shift right 1.
			case 3:
				return $pattern[1] . $pattern[0] . $pattern[2]; // ) swap first 2.
			case 4:
				return $pattern[0] . $pattern[2] . $pattern[1]; // [ swap last 2.
			case 5:
				return $pattern[2] . $pattern[1] . $pattern[0]; // ] flip.
		}
	}

	/**
	 * Get all permutations for a 3 digit string.
	 *
	 * @param string $input the 3 digit string.
	 * @return array
	 */
	private function permutations( $input ) {
		$permutations = array();
		for ( $rule_index = 0; $rule_index < 6; ++ $rule_index ) {
			$permutations[] = $this->rule_adjustment( $input, $rule_index );
		}
		return $permutations;
	}

	/**
	 * Replace two character permutations
	 */
	public function two_character_permutations() {
		$symbols = $this->two_character_permutations_symbols;
		$input   = $this->string;
		$to      = strlen( $input ) - 1;
		$counts  = array();

		for ( $i = 0; $i < $to; ++ $i ) {
			$pattern = array( $input[ $i ], $input[ $i + 1 ] );
			sort( $pattern );
			$pattern            = implode( '', $pattern );
			$counts[ $pattern ] = ( isset( $counts[ $pattern ] ) ? $counts[ $pattern ] : 0 ) + 1;

			if ( $i < $to - 1 ) {
				$pattern = array( $input[ $i ], $input[ $i + 2 ] );
				sort( $pattern );
				$pattern            = implode( '', $pattern );
				$counts[ $pattern ] = ( isset( $counts[ $pattern ] ) ? $counts[ $pattern ] : 0 ) + 1;
			}
		}

		$top = $this->top_pattern( $counts );
		if ( $counts[ $top ] <= 2 ) {
			return;
		}

		$flip = $top[1] . $top[0];
		for ( $char_index = 0; $char_index < $to; ++ $char_index ) {
			$current = $input[ $char_index ] . $input[ $char_index + 1 ];
			if ( $current === $top || $current === $flip ) {
				if ( $current === $flip ) {
					$top = $flip;
				}
				$first_index     = $char_index;
				$first_character = $symbols[0];
				$first_pattern   = $current;
				break;
			}

			if ( $char_index < $to - 1 ) {
				$current = $input[ $char_index ] . $input[ $char_index + 2 ];
				if ( $current === $top || $current === $flip ) {
					if ( $current === $flip ) {
						$top = $flip;
					}
					$first_index     = $char_index;
					$first_character = $symbols[2];
					$first_pattern   = substr( $input, $char_index, 3 );
					break;
				}
			}
		}

		$result     = substr( $input, 0, $first_index ) . $first_character . $first_pattern;
		$remaining  = substr( $input, $first_index + strlen( $first_pattern ) );
		$characters = str_split( $symbols );

		foreach ( $characters as $character_index => $c ) {
			switch ( $character_index ) {
				case 0:
					$remaining = str_replace( $top, $c, $remaining );
					break;

				case 1:
					$remaining = str_replace( $flip, $c, $remaining );
					break;

				case 2:
					$remaining = preg_replace( '/(' . addslashes( $top[0] ) . ')(.{1})(' . addslashes( $top[1] ) . ')/', $c . '$2', $remaining );
					break;

				case 3:
					$remaining = preg_replace( '/(' . addslashes( $top[1] ) . ')(.{1})(' . addslashes( $top[0] ) . ')/', $c . '$2', $remaining );
					break;
			}
		}

		$this->string = $result . $remaining;
	}

	/**
	 * Run all compression functions in order.
	 */
	public function compress() {
		$this->to_base_64();
		$this->counter();
		$this->two_most_common_patterns();
		$this->third_most_common_pattern();
		$this->common_three_character_patterns();
		$this->common_special_patterns();
		$this->top_two_patterns();
		$this->three_character_permutations();
		$this->two_character_permutations();
	}
}
