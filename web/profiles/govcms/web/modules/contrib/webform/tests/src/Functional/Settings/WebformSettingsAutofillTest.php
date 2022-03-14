<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form autofill.
 *
 * @group webform
 */
class WebformSettingsAutofillTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_autofill'];

  /**
   * Test webform submission form autofill.
   */
  public function testAutofill() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $webform = Webform::load('test_form_autofill');

    // Check that elements are empty.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertNoRaw('This submission has been autofilled with your previous submission.');

    // Check that 'textfield_excluded' is empty.
    $this->assertFieldByName('textfield_excluded', '');

    // Check that 'textfield_autofill' is empty.
    $this->assertFieldByName('textfield_autofill', '');

    // Check that 'telephone_excluded' is empty.
    $this->assertFieldByName('telephone_excluded[type]', '');
    $this->assertFieldByName('telephone_excluded[phone]', '');
    $this->assertFieldByName('telephone_excluded[ext]', '');

    // Check that 'telephone_autofill' is empty.
    $this->assertFieldByName('telephone_autofill[type]', '');
    $this->assertFieldByName('telephone_autofill[phone]', '');
    $this->assertFieldByName('telephone_autofill[ext]', '');

    // Check that 'telephone_autofill_partial' is empty.
    $this->assertFieldByName('telephone_autofill_partial[type]', '');
    $this->assertFieldByName('telephone_autofill_partial[phone]', '');
    $this->assertFieldByName('telephone_autofill_partial[ext]', '');

    // Check that 'telephone_autofill_partial_multiple' is empty.
    $this->assertFieldByName('telephone_autofill_partial_multiple[items][0][_item_][type]', '');
    $this->assertFieldByName('telephone_autofill_partial_multiple[items][0][_item_][phone]', '');
    $this->assertFieldByName('telephone_autofill_partial_multiple[items][0][_item_][ext]', '');

    // Create a submission.
    $edit = [
      'textfield_excluded' => '{textfield_excluded}',
      'textfield_autofill' => '{textfield_autofill}',
      'telephone_excluded[type]' => 'Cell',
      'telephone_excluded[phone]' => '+1 111-111-1111',
      'telephone_excluded[ext]' => '111',
      'telephone_autofill[type]' => 'Cell',
      'telephone_autofill[phone]' => '+1 222-222-2222',
      'telephone_autofill[ext]' => '222',
      'telephone_autofill_partial[type]' => 'Cell',
      'telephone_autofill_partial[phone]' => '+1 333-333-3333',
      'telephone_autofill_partial[ext]' => '333',
      'telephone_autofill_partial_multiple[items][0][_item_][type]' => 'Cell',
      'telephone_autofill_partial_multiple[items][0][_item_][phone]' => '+1 444-444-4444',
      'telephone_autofill_partial_multiple[items][0][_item_][ext]' => '444',
    ];
    $this->postSubmission($webform, $edit);

    // Get autofilled submission form.
    $this->drupalGet('/webform/test_form_autofill');

    // Check that 'textfield_excluded' is empty.
    $this->assertNoFieldByName('textfield_excluded', '{textfield_excluded}');
    $this->assertFieldByName('textfield_excluded', '');

    // Check that 'textfield_autofill' is autofilled.
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');

    // Check that 'telephone_excluded[' is empty.
    $this->assertFieldByName('telephone_excluded[type]', '');
    $this->assertFieldByName('telephone_excluded[phone]', '');
    $this->assertFieldByName('telephone_excluded[ext]', '');

    // Check that 'telephone__autofill' is autofilled.
    $this->assertFieldByName('telephone_autofill[type]', 'Cell');
    $this->assertFieldByName('telephone_autofill[phone]', '+1 222-222-2222');
    $this->assertFieldByName('telephone_autofill[ext]', '222');

    // Check that 'telephone__autofill_partial' is partially autofilled.
    $this->assertFieldByName('telephone_autofill_partial[type]', 'Cell');
    $this->assertFieldByName('telephone_autofill_partial[phone]', '');
    $this->assertFieldByName('telephone_autofill_partial[ext]', '');

    // Check that 'telephone__autofill_partial_multiple' is partially autofilled.
    $this->assertFieldByName('telephone_autofill_partial_multiple[items][0][_item_][type]', 'Cell');
    $this->assertFieldByName('telephone_autofill_partial_multiple[items][0][_item_][phone]', '');
    $this->assertFieldByName('telephone_autofill_partial_multiple[items][0][_item_][ext]', '');

    // Check that default configuration message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertRaw('This submission has been autofilled with your previous submission.');

    // Clear default autofill message.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_autofill_message', '')
      ->save();

    // Check no autofill message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertNoRaw('This submission has been autofilled with your previous submission.');

    // Set custom automfill message.
    $webform
      ->setSetting('autofill_message', '{autofill_message}')
      ->save();

    // Check custom autofill message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertRaw('{autofill_message}');
  }

}
