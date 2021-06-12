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
	 * The active pattern matches found when calling self::update_pattern_meta.
	 *
	 * @var array<int> $active_pattern_matches
	 */
	private $active_pattern_matches = array();

	/**
	 * The active pattern count when calling self::update_pattern_meta.
	 *
	 * @var int $active_pattern_number
	 */
	private $active_pattern_number = 0;

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
	 *
	 * @return void
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

			$quotient = (int) $set[0] + ( (int) $set[1] << 8 ) + ( (int) $set[2] << 16 );
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
	 * Convert from base 64 back to decimal.
	 *
	 * @return void
	 */
	public function to_decimal() {
		$input  = $this->string;
		$length = strlen( $input );
		$output = array();
		for ( $i = 0; $i <= $length; $i += 4 ) {
			$str    = substr( $input, $i, 4 );
			$to     = strlen( $str );
			$number = 0;
			for ( $j = 0; $j < $to; ++ $j ) {
				$number = 64 * $number;
				$pos    = strpos( $this->base_64_symbols, $str[ $j ] );
				if ( is_int( $pos ) ) {
					$number += $pos;
				}
			}
			$first   = $number >> 16;
			$number -= $first << 16;
			$second  = $number >> 8;
			$number -= $second << 8;
			$output  = array_merge( $output, array( $number, $second, $first ) );
		}
		$count = count( $output );
		while ( 0 === $output[ $count - 1 ] ) {
			array_pop( $output );
			-- $count;
		}
		$this->string = implode( ',', $output );
	}

	/**
	 * Counter repeat instances of characters and reduce
	 *
	 * @return void
	 */
	public function counter() {
		$symbols    = $this->counter_symbols;
		$previous   = '';
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
	 * Reverse the effect of calling counter
	 *
	 * @return void
	 */
	public function decounter() {
		$symbols    = $this->counter_symbols;
		$output     = '';
		$previous   = '';
		$characters = str_split( $this->string );
		foreach ( $characters as $c ) {
			$index = strpos( $symbols, $c );

			if ( false !== $index ) {
				++ $index;
				for ( $i = 0; $i <= $index; ++ $i ) {
					$output .= $previous;
				}
			} else {
				$output .= $c;
			}

			$previous = $c;
		}

		$this->string = $output;
	}

	/**
	 * Add previous character to output.
	 *
	 * @param string $previous the previous character value.
	 * @param int    $count the repeated count for the previous character value.
	 * @param string $symbols the counter symbols.
	 * @return string
	 */
	private function add_previous_character_to_output( $previous, $count, $symbols ) {
		$output = $previous;
		if ( $count > 1 ) {
			$output .= 2 === $count ? $previous : $symbols[ $count - 3 ];
		}
		return $output;
	}

	/**
	 * Reduce the two most common patterns to single characters
	 *
	 * @return void
	 */
	public function two_most_common_patterns() {
		$this->string = str_replace( array( '00', '$$' ), array( '@', '=' ), $this->string );
	}

	/**
	 * Reverse the effect of two_most_common_patterns
	 *
	 * @return void
	 */
	public function unsub_two_most_common_patterns() {
		$this->string = str_replace( array( '@', '=' ), array( '00', '$$' ), $this->string );
	}

	/**
	 * Replace the third most common pattern
	 *
	 * @return void
	 */
	public function third_most_common_pattern() {
		$this->string = str_replace( '0^', "'", $this->string );
	}

	/**
	 * Reverse the effect of third_most_common_pattern
	 *
	 * @return void
	 */
	public function unsub_third_most_common_pattern() {
		$this->string = str_replace( "'", '0^', $this->string );
	}

	/**
	 * Replace common three character patterns
	 *
	 * @return void
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
	 * @return array<string>
	 */
	private function get_three_character_patterns() {
		return array( '3$0', 'Y0$', 'M0*', '!f$', '@20', '080', '0Y0', '0f0', '$`$', '3w0', 'fYf', '0fU', '"23', 'c01', 'Y07', '0fY', '!3$', '020', '3M3', 'Y@f', '0fM', '$"3', '$^Y', '640', '030', '1Y0', '1M0', 'Yf=', '@3w', '0c0', '"22', '0M0', '$3$', '!$^', '3$v', '0g0', 'o\'o', 'M3"', '"1M', 'f$^', 'M3M', '0s0', '0v0', '@80', '$*"', '03"' );
	}

	/**
	 * Reverse the effect of get_three_character_patterns
	 *
	 * @return void
	 */
	public function unsub_common_three_character_patterns() {
		$patterns = $this->get_three_character_patterns();
		$input    = $this->string;
		for ( $index = count( $patterns ) - 1; $index >= 0; -- $index ) {
			$input = str_replace( '"' . $this->base_64_symbols[ $index ], $patterns[ $index ], $input );
		}
		$this->string = $input;
	}

	/**
	 * Replace common special patterns
	 *
	 * @return void
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

		return '';
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
	 * @return array<string>
	 */
	private function get_other_patterns() {
		return array( '$ $ $', '"  "  "', '$M  $', '01  0', '= $  $', '01  \'', 'M f  0', '0 "  0', '0 "  "', '0  "  0', 'M " M', '3 0 "', 'M  0  "', 'm  q  G', '$  $  f', 'Y $ Y', '"  f  "', '1w0', '0  " "', '$^ $', '1 "V', '1" Y', '" " M', '$M   $', '0  "  "', '" fw' );
	}

	/**
	 * Reverse the effect of common_special_patterns
	 *
	 * @return void
	 */
	public function unsub_common_special_patterns() {
		$input           = $this->string;
		$input_length    = strlen( $input );
		$patterns        = $this->get_three_character_patterns();
		$other_patterns  = $this->get_other_patterns();
		$patterns_length = count( $patterns );

		for ( $index = count( $other_patterns ) - 1; $index >= 0; -- $index ) {
			$character_index = $patterns_length + $index;
			$character       = $this->char_at( $character_index );
			$search          = '"' . $character;

			if ( false !== strpos( $input, $search ) ) {
				$unsubbed_string = '';
				$to              = strlen( $input );
				for ( $string_index = 0; $string_index < $to; ++ $string_index ) {
					if ( $string_index + 1 < $input_length && '"' === $input[ $string_index ] && $character === $input[ $string_index + 1 ] ) {
						$pattern        = $other_patterns[ $index ];
						$pattern_length = strlen( $pattern );
						$i              = 2;
						for ( $j = 0; $j < $pattern_length; ++ $j ) {
							if ( ' ' !== $pattern[ $j ] ) {
								$unsubbed_string .= $pattern[ $j ];
							} else {
								$unsubbed_string .= $input[ $string_index + $i ];
								++ $i;
							}
						}

						$string_index += $pattern_length - 2;
					} else {
						$unsubbed_string .= $input[ $string_index ];
					}
				}

				$input = $unsubbed_string;
			}
		}

		$this->string = $input;
	}

	/**
	 * Replace the top two patterns
	 *
	 * @return void
	 */
	public function top_two_patterns() {
		$this->sub_top_pattern( ';' );
		$this->sub_top_pattern( ':' );
	}

	/**
	 * Determine the most frequent appearing pattern in the active string and replace it with the specified $character.
	 *
	 * @param string $character the character we're replacing the top pattern with.
	 * @return void
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
		if ( ! array_key_exists( $top, $counts ) || $counts[ $top ] <= 2 ) {
			return;
		}

		$first_index  = strpos( $input, $top );
		$this->string = substr( $input, 0, $first_index ) . $character . $top . str_replace( $top, $character, substr( $input, $first_index + 2 ) );
	}

	/**
	 * Determine from a list of counts by array, which pattern appears the most frequently.
	 *
	 * @param array<int> $counts the full array of counts by pattern index.
	 * @return string the pattern with the highest count.
	 */
	private function top_pattern( $counts ) {
		$top = '';

		foreach ( $counts as $key => $count ) {
			if ( '' === $top || $count > $counts[ $top ] ) {
				$top = (string) $key;
			}
		}

		return "$top";
	}

	/**
	 * Reverse the ffect of calling top_two_patterns
	 *
	 * @return void
	 */
	public function unsub_top_two_patterns() {
		$this->unsub_pattern( ':' );
		$this->unsub_pattern( ';' );
	}

	/**
	 * Reverse the effect of sub_top_pattern
	 *
	 * @param string $character we're replacing back.
	 * @return void
	 */
	private function unsub_pattern( $character ) {
		$input       = $this->string;
		$first_index = strpos( $input, $character );
		if ( false === $first_index ) {
			return;
		}
		$pattern      = $input[ $first_index + 1 ] . $input[ $first_index + 2 ];
		$input        = str_replace( $character . $pattern, $pattern, $input );
		$input        = str_replace( $character, $pattern, $input );
		$this->string = $input;
	}

	/**
	 * Replace three character permutations
	 *
	 * @return void
	 */
	public function three_character_permutations() {
		$symbols = $this->three_character_permutations_symbols;
		$input   = $this->string;
		$to      = strlen( $input ) - 2;

		$counts = array();
		for ( $i = 0; $i < $to; ++ $i ) {
			$pattern            = substr( $input, $i, 3 );
			$counts[ $pattern ] = $this->pattern_number( $pattern, $input );
		}

		$top = $this->top_pattern( $counts );
		if ( $counts[ $top ] <= 2 ) {
			return;
		}

		$first_index = strpos( $input, $top );
		$matches     = $this->pattern_matches( $top, $input );
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
	 * @return void
	 */
	private function update_pattern_meta( $pattern, $input ) {
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

		$this->active_pattern_number  = $number;
		$this->active_pattern_matches = array_values( $matches );
	}

	/**
	 * Check a string for any pattern permutation matches.
	 *
	 * @param string $pattern the base pattern we're checking for.
	 * @param string $input the input we're checking the pattern for.
	 * @return array<int>
	 */
	private function pattern_matches( $pattern, $input ) {
		$this->update_pattern_meta( $pattern, $input );
		return $this->active_pattern_matches;
	}

	/**
	 * Check a string for any pattern permutation matches.
	 *
	 * @param string $pattern the base pattern we're checking for.
	 * @param string $input the input we're checking the pattern for.
	 * @return int
	 */
	private function pattern_number( $pattern, $input ) {
		$this->update_pattern_meta( $pattern, $input );
		return $this->active_pattern_number;
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
		return $pattern;
	}

	/**
	 * Get all permutations for a 3 digit string.
	 *
	 * @param string $input the 3 digit string.
	 * @return array<string>
	 */
	private function permutations( $input ) {
		$permutations = array();
		for ( $rule_index = 0; $rule_index < 6; ++ $rule_index ) {
			$permutations[] = $this->rule_adjustment( $input, $rule_index );
		}
		return $permutations;
	}

	/**
	 * Reverse the effect of three_character_permutations
	 *
	 * @return void
	 */
	public function unsub_three_character_permutations() {
		$input       = $this->string;
		$symbols     = $this->three_character_permutations_symbols;
		$first_index = strpos( $input, $symbols[0] );

		if ( false === $first_index ) {
			return;
		}

		$pattern   = substr( $input, $first_index + 1, 3 );
		$result    = substr( $input, 0, $first_index ) . $pattern;
		$remaining = substr( $input, $first_index + 4 );
		for ( $rule_index = 5; $rule_index >= 0; -- $rule_index ) {
			$check = $symbols[ $rule_index ];
			if ( false !== strpos( $remaining, $check ) ) {
				$remaining = str_replace( $check, $this->rule_adjustment( $pattern, $rule_index ), $remaining );
			}
		}

		$this->string = $result . $remaining;
	}

	/**
	 * Replace two character permutations
	 *
	 * @return void
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

		$first_index     = -1;
		$first_character = '';
		$flip            = $top[1] . $top[0];
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
					$remaining = preg_replace( '/(' . preg_quote( $top[0], '/' ) . ')(.{1})(' . preg_quote( $top[1], '/' ) . ')/', $c . '${2}', $remaining );
					break;

				case 3:
					$remaining = preg_replace( '/(' . preg_quote( $top[1], '/' ) . ')(.{1})(' . preg_quote( $top[0], '/' ) . ')/', $c . '${2}', $remaining );
					break;
			}
		}

		$this->string = $result . $remaining;
	}

	/**
	 * Reverse the effect of two_character_permutations
	 *
	 * @return void
	 */
	public function unsub_two_character_permutations() {
		$input   = $this->string;
		$symbols = $this->two_character_permutations_symbols;
		$length  = strlen( $symbols );

		$first_index     = -1;
		$first_character = '';
		for ( $rule_index = 0; $rule_index < $length; ++ $rule_index ) {
			$index = strpos( $input, $symbols[ $rule_index ] );
			if ( false !== $index && ( -1 === $first_index || $index < $first_index ) ) {
				$first_index     = $index;
				$first_character = $symbols[ $rule_index ];
			}
		}

		if ( -1 === $first_index ) {
			return;
		}

		$trailing = substr( $input, $first_index + 1, 3 );
		$result   = substr( $input, 0, $first_index );

		switch ( $first_character ) {
			case $symbols[0]:
				$characters = $trailing[0] . $trailing[1];
				$result    .= substr( $trailing, 0, 2 );
				$step       = 3;
				break;

			case $symbols[2]:
				$characters = $trailing[0] . $trailing[2];
				$result    .= $trailing;
				$step       = 4;
				break;

			default:
				return;
		}

		$remaining = substr( $input, $first_index + $step );
		for ( $char_index = $length - 1; $char_index >= 0; -- $char_index ) {
			$c = $symbols[ $char_index ];

			if ( false !== strpos( $remaining, $c ) ) {
				switch ( $char_index ) {
					case 0:
						$comparison = $c;
						$replace    = $characters;
						$remaining  = str_replace( $comparison, $replace, $remaining );
						break;

					case 1:
						$comparison = $c;
						$replace    = $characters[1] . $characters[0];
						$remaining  = str_replace( $comparison, $replace, $remaining );
						break;

					case 2:
						$comparison = '(' . preg_quote( $c, '/' ) . ')(.{1})';
						$replace    = $characters[0] . '${2}' . $characters[1];
						$remaining  = preg_replace( "/$comparison/", $replace, $remaining );
						break;

					case 3:
						$comparison = '(' . preg_quote( $c, '/' ) . ')(.{1})';
						$replace    = $characters[1] . '${2}' . $characters[0];
						$remaining  = preg_replace( "/$comparison/", $replace, $remaining );
						break;
				}
			}
		}

		$this->string = $result . $remaining;
	}

	/**
	 * Run all compression functions in order.
	 *
	 * @return void
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

	/**
	 * Run all decompression functions in order to reverse the effect of compress.
	 *
	 * @return void
	 */
	public function decompress() {
		$this->unsub_two_character_permutations();
		$this->unsub_three_character_permutations();
		$this->unsub_top_two_patterns();
		$this->unsub_common_special_patterns();
		$this->unsub_common_three_character_patterns();
		$this->unsub_third_most_common_pattern();
		$this->unsub_two_most_common_patterns();
		$this->decounter();
		$this->to_decimal();
	}
}
