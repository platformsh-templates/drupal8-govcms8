<?php

namespace Drupal\Tests\simple_oauth\Functional;

use Drupal\Component\Serialization\Json;

/**
 * @group simple_oauth
 */
class PasswordFunctionalTest extends TokenBearerFunctionalTestBase {

  /**
   * @var string
   */
  protected $path;

  /**
   * Ensure public clients still validate client secrets when they are sent.
   */
  public function testValidateSecretEvenIfPublic() {
    $this->client->set('confidential', FALSE)->save();
    // 1. Send an invalid secret.
    $invalid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      'client_secret' => 'invalidClientSecret',
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];
    $response = $this->post($this->url, $invalid_payload);
    $this->assertEquals(401, $response->getStatusCode());
    // 2. A valid secret is validated correctly.
    $valid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];
    $response = $this->post($this->url, $valid_payload);
    $this->assertValidTokenResponse($response, TRUE);
    // 3. The client has a secret registered, so it's always required.
    $invalid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      // No client secret sent.
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];
    $response = $this->post($this->url, $invalid_payload);
    $this->assertEquals(401, $response->getStatusCode());
  }

  /**
   * Test the valid Password grant.
   */
  public function testPasswordGrant() {
    $this->client->set('confidential', FALSE);
    $this->client->set('secret', NULL)->save();
    // 1. Test the valid request.
    $valid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      // This is a public client. PKCE and secrets are tested elsewhere.
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];
    $response = $this->post($this->url, $valid_payload);
    $this->assertValidTokenResponse($response, TRUE);
    // Repeat the request but pass an obtained access token as a header in
    // order to check the authentication in parallel, which will precede
    // the creation of a new token.
    $parsed = Json::decode((string) $response->getBody());
    $response = $this->post($this->url, $valid_payload, [
      'headers' => ['Authorization' => 'Bearer ' . $parsed['access_token']],
    ]);
    $this->assertValidTokenResponse($response, TRUE);

    // 2. Test the valid request without scopes.
    $payload_no_scope = $valid_payload;
    unset($payload_no_scope['scope']);
    $response = $this->post($this->url, $payload_no_scope);
    $this->assertValidTokenResponse($response, TRUE);

    // 3. Test valid request using HTTP Basic Auth.
    $payload_no_client = $valid_payload;
    unset($payload_no_client['client_id']);
    unset($payload_no_client['client_secret']);
    $response = $this->post($this->url, $payload_no_scope,
      [
        'auth' => [
          $this->client->uuid(),
          $this->clientSecret,
        ],
      ]
    );
    $this->assertValidTokenResponse($response, TRUE);
  }

  /**
   * Test invalid Password grant.
   */
  public function testMissingPasswordGrant() {
    $valid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];

    $data = [
      'grant_type' => [
        'error' => 'invalid_grant',
        'code' => 400,
      ],
      'client_id' => [
        'error' => 'invalid_request',
        'code' => 400,
      ],
      'client_secret' => [
        'error' => 'invalid_client',
        'code' => 401,
      ],
      'username' => [
        'error' => 'invalid_request',
        'code' => 400,
      ],
      'password' => [
        'error' => 'invalid_request',
        'code' => 400,
      ],
    ];
    foreach ($data as $key => $value) {
      $invalid_payload = $valid_payload;
      unset($invalid_payload[$key]);
      $response = $this->post($this->url, $invalid_payload);
      $parsed_response = Json::decode((string) $response->getBody());
      $this->assertSame($value['error'], $parsed_response['error'], sprintf('Correct error code %s for %s.', $value['error'], $key));
      $this->assertSame($value['code'], $response->getStatusCode(), sprintf('Correct status code %d for %s.', $value['code'], $key));
    }
  }

  /**
   * Test invalid Password grant.
   */
  public function testInvalidPasswordGrant() {
    $valid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];

    $data = [
      'grant_type' => [
        'error' => 'invalid_grant',
        'code' => 400,
      ],
      'client_id' => [
        'error' => 'invalid_client',
        'code' => 401,
      ],
      'client_secret' => [
        'error' => 'invalid_client',
        'code' => 401,
      ],
      'username' => [
        'error' => 'invalid_grant',
        'code' => 400,
      ],
      'password' => [
        'error' => 'invalid_grant',
        'code' => 400,
      ],
    ];
    foreach ($data as $key => $value) {
      $invalid_payload = $valid_payload;
      $invalid_payload[$key] = $this->getRandomGenerator()->string();
      $response = $this->post($this->url, $invalid_payload);
      $parsed_response = Json::decode((string) $response->getBody());
      $this->assertSame($value['error'], $parsed_response['error'], sprintf('Correct error code %s for %s.', $value['error'], $key));
      $this->assertSame($value['code'], $response->getStatusCode(), sprintf('Correct status code %d for %s.', $value['code'], $key));
    }
    // Test sending an invalid secret; this is a public client, so it's not
    // required, but demonstrates the secret is validated when sent.
    $this->client->set('confidential', FALSE);
    $payload_with_invalid_secret = ['client_secret' => 'invalid'] + $valid_payload;
    $response = $this->post($this->url, $payload_with_invalid_secret);
    $this->assertEquals(401, $response->getStatusCode());
  }

}
