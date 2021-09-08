<?php

namespace PMPro\Tests\Functions;

use PHP_CodeSniffer\Generators\HTML;
use PMPro\Test_Support\TestCases\TestCase;

/**
 * @group pmpro-functions
 * @group pmpro-sessions
 */
class SessionTest extends TestCase {

	/**
	 * @covers ::pmpro_kses()
	 * @covers ::pmpro_kses_allowed_html()
	 */
	public function test_pmpro_kses_with_email_context_with_allowed_html() {
		$html = file_get_contents( codecept_data_dir( 'kses/email-allowed.html' ) );

		$this->assertEquals( $html, pmpro_kses( $html ) );
	}

	/**
	 * @covers ::pmpro_kses()
	 * @covers ::pmpro_kses_allowed_html()
	 */
	public function test_pmpro_kses_with_email_context_with_disallowed_html() {
		$html = file_get_contents( codecept_data_dir( 'kses/email-disallowed.html' ) );

		$this->assertEquals( 'Test content.', trim( pmpro_kses( $html ) ) );
	}
}