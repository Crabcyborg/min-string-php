<?php

/**
 * Unit Test class for MinString.php
 */
class UnitTest_MinString extends \PHPUnit\Framework\TestCase {

	/**
	 * Test MinString::to_base_64
	 */
	public function test_to_base_64() {
		$this->assert_before_and_after( $this->get_raw_value(), 'to_base_64', $this->get_expected_base_64_value() );
	}

	/**
	 * Assert that a value has the expected result after calling the specified MinString function.
	 *
	 * @param string $before the value before calling the function.
	 * @param string $function the function we're calling and testing the result of.
	 * @param string $expected the value we expect back after calling $function.
	 */
	private function assert_before_and_after( $before, $function, $expected ) {
		$min = new MinString( $before );
		$min->$function();
		$this->assertEquals( $expected, $min->get_active_string() );
	}

	/**
	 * Get the raw csv value for the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_raw_value() {
		return '27,48,0,0,0,0,31,0,0,0,0,0,127,128,0,0,0,1,255,248,0,0,0,7,255,254,0,0,0,31,255,255,0,0,0,127,255,255,0,0,0,255,255,255,0,0,127,255,255,255,0,0,255,255,255,254,0,1,255,255,255,252,12,3,255,255,255,224,19,127,255,255,252,0,33,255,255,255,254,0,65,255,255,255,254,0,129,255,255,255,254,0,15,255,255,255,254,0,8,127,255,255,255,0,16,127,255,255,255,0,16,62,127,255,255,0,32,29,191,255,254,128,32,9,111,128,204,64,0,1,107,0,102,0,0,2,98,0,35,0,0,4,70,0,33,0,0,0,132,0,16,128,0,1,8,0,0,128,0,0,8,0,0,64';
	}

	/**
	 * Get the base64 value for the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_base_64_value() {
		return '030r0000000v0000081$0g000fz$1M000fX$7M000f$$vM000f$$$M000f$$$TY00f$$$$Y00fX$$$Y13fP$$$Y34!3$$$Z$8g3Y$$$$gg3!$$$$wg3!$$$$3M3!$$$$203!$$Z$403$$$Z$403$$TY!803$$XYt883!w6Y9043c06I1001C0682000z04o4000x08g0080g00w1080000w00400';
	}

	/**
	 * Test MinString::counter
	 */
	public function test_counter() {
		$this->assert_before_and_after( $this->get_expected_base_64_value(), 'counter', $this->get_expected_counter_value() );
	}

	/**
	 * Get the counter value for the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_counter_value() {
		return '030r0~v0-81$0g0^fz$1M0^fX$7M0^f$$vM0^f$^M0^f$^TY00f$*Y00fX$^Y13fP$^Y34!3$^Z$8g3Y$*gg3!$*wg3!$*3M3!$*203!$$Z$403$^Z$403$$TY!803$$XYt883!w6Y9043c06I1001C06820^z04o40^x08g0080g00w1080*w00400';
	}

	/**
	 * Test MinString::two_most_common_patterns
	 */
	public function test_two_most_common_patterns() {
		$this->assert_before_and_after( $this->get_expected_counter_value(), 'two_most_common_patterns', $this->get_expected_two_most_common_patterns_value() );
	}

	/**
	 * Get the expected result from calling two_most_common_patterns with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_two_most_common_patterns_value() {
		return '030r0~v0-81$0g0^fz$1M0^fX$7M0^f=vM0^f$^M0^f$^TY@f$*Y@fX$^Y13fP$^Y34!3$^Z$8g3Y$*gg3!$*wg3!$*3M3!$*203!=Z$403$^Z$403=TY!803=XYt883!w6Y9043c06I1@1C06820^z04o40^x08g@80g@w1080*w@4@';
	}

	/**
	 * Test MinString::third_most_common_pattern
	 */
	public function test_third_most_common_pattern() {
		$this->assert_before_and_after( $this->get_expected_two_most_common_patterns_value(), 'third_most_common_pattern', $this->get_expected_third_most_common_pattern_value() );
	}

	/**
	 * Get the expected result when calling third_most_common_pattern with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_third_most_common_pattern_value() {
		return "030r0~v0-81$0g'fz$1M'fX$7M'f=vM'f$^M'f$^TY@f$*Y@fX$^Y13fP$^Y34!3$^Z$8g3Y$*gg3!$*wg3!$*3M3!$*203!=Z$403$^Z$403=TY!803=XYt883!w6Y9043c06I1@1C0682'z04o4'x08g@80g@w1080*w@4@";
	}

	/**
	 * Test MinString::common_three_character_patterns
	 */
	public function test_common_three_character_patterns() {
		$this->assert_before_and_after( $this->get_expected_third_most_common_pattern_value(), 'common_three_character_patterns', $this->get_expected_common_three_character_patterns_value() );
	}

	/**
	 * Get the expected result when calling common_three_character_patterns with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_common_three_character_patterns_value() {
		return '"or0~v0-81$0g\'fz$1M\'fX$7M\'f=vM\'"DM\'"DT"j"IjX"m13fP"m34"g^Z$8g3Y$*gg3!$*wg3!"Ii!$*203!=Z$403$^Z$403=TY!803=XYt883!w6Y9043c06I1@1C0682\'z04o4\'x08g"Hg@w1"5*w@4@';
	}

	/**
	 * Test MinString::common_special_patterns
	 *
	 * @group mike
	 */
	public function test_common_special_patterns() {
		$this->assert_before_and_after( $this->get_expected_common_three_character_patterns_value(), 'common_special_patterns', $this->get_expected_common_special_patterns_value() );
	}

	/**
	 * Get the expected result when calling common_special_patterns with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_common_special_patterns_value() {
		return '"or0~v0-81$0g\'fz$1M\'fX$7M\'f=v"U\'D\'"DT"j"IjX"m13fP"m34"g^Z$8g3Y$*gg3!$*wg3!"Ii!$*203!=Z$403"*Z403=TY!803=XYt883!w6Y9043c06I1@1C0682\'z04o4\'x08g"Hg@w1"5*w@4@';
	}

	/**
	 * Test MinString::top_two_patterns
	 */
	public function test_top_two_patterns() {
		$this->assert_before_and_after( $this->get_expected_common_special_patterns_value(), 'top_two_patterns', $this->get_expected_top_two_patterns_value() );
	}

	/**
	 * Test MinString::sub_top_pattern
	 */
	public function test_sub_top_pattern() {
		$min = new MinString( $this->get_expected_common_special_patterns_value() );
		$min->sub_top_pattern( ';' );
		$this->assertEquals( $this->get_expected_sub_top_pattern_semi_colon_value(), $min->get_active_string() );

		$min->sub_top_pattern( ':' );
		$this->assertEquals( $this->get_expected_top_two_patterns_value(), $min->get_active_string() );
	}

	/**
	 * Get the expected result when calling sub_top_pattern( ';' ) with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_sub_top_pattern_semi_colon_value() {
		return '"or0~v0-81$0g\'fz$1M\'fX$7M\'f=v"U\'D\'"DT"j"IjX"m13fP"m34"g^Z$8g3Y$*gg;3!$*wg;"Ii!$*20;=Z$403"*Z403=TY!803=XYt88;w6Y9043c06I1@1C0682\'z04o4\'x08g"Hg@w1"5*w@4@';
	}

	/**
	 * Get the expected result when calling top_two_patterns with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_top_two_patterns_value() {
		return '"or0~v0-81$0g:\'fz$1M:X$7M:=v"U\'D\'"DT"j"IjX"m13fP"m34"g^Z$8g3Y$*gg;3!$*wg;"Ii!$*20;=Z$403"*Z403=TY!803=XYt88;w6Y9043c06I1@1C0682\'z04o4\'x08g"Hg@w1"5*w@4@';
	}

	/**
	 * Test MinString::three_character_permutations
	 */
	public function test_three_character_permutations() {
		$this->assert_before_and_after( $this->get_expected_top_two_patterns_value(), 'three_character_permutations', $this->get_expected_three_character_permutations_value() );
	}

	/**
	 * Get the expected result when calling three_character_permutations with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_three_character_permutations_value() {
		return '"or0~v0-81$0g:\'fz$1M:X$7M:=v"U\'D\'"DT"j"IjX"m13fP"m34"g^Z$8g3Y$*gg;3!$*wg;"Ii!$*20;=Z$<403"*Z<=TY!803=XYt88;w6Y9)c06I1@1C0682\'z04o4\'x08g"Hg@w1"5*w@4@';
	}

	/**
	 * Test MinString::two_character_permutations
	 */
	public function test_two_character_permutations() {
		$this->assert_before_and_after( $this->get_expected_three_character_permutations_value(), 'two_character_permutations', $this->get_expected_two_character_permutations_value() );
	}

	/**
	 * Get the expected result when calling two_character_permutations with the Bumblebee data set.
	 *
	 * @return string
	 */
	private function get_expected_two_character_permutations_value() {
		return '"or0~v+0-81$0g:\'fz$1M:X$7M:=v"U\'D\'"DT"j"IjX"m13fP"m34"g^Z$8g3Y$*gg;3!$*wg;"Ii!$*20;=Z$<403"*Z<=TY!}3=XYt88;w6Y9)c06I1@1C+62\'z04o4\'x{g"Hg@w1"5*w@4@';
	}

	/**
	 * Test MinString::compress
	 */
	public function test_compress() {
		$this->assert_before_and_after( $this->get_raw_value(), 'compress', $this->get_expected_two_character_permutations_value() );
	}
}
