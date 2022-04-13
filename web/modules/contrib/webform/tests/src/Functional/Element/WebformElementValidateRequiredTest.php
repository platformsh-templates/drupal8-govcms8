<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform required validation.
 *
 * @group webform
 */
class WebformElementValidateRequiredTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_required'];

  /**
   * Tests pattern validation.
   */
  public function testPattern() {
    // Check that HTML tags are stripped  from required error attribute.
    $this->drupalGet('/webform/test_element_validate_required');
    $this->assertRaw('<input data-webform-required-error="This is a custom required message" data-drupal-selector="edit-required-error-textfield" type="text" id="edit-required-error-textfield" name="required_error_textfield" value="" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check that HTML tags are rendered in validation message.
    $this->drupalPostForm('/webform/test_element_validate_required', [], 'Submit');
    $this->assertRaw(' <li>required_error_textfield_<em>html</em> field is required.</li>');
    $this->assertRaw('<li>This is a <em>custom required message</em></li>');
  }

}
