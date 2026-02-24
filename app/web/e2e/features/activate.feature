Feature: Account Activation

  Scenario: Valid token redirects to login with success
    Given the activation API returns 200
    When I visit the activation page with token "valid-token"
    Then I am redirected to "/login?activation=success"

  Scenario: Already used token redirects with already_activated
    Given the activation API returns 409
    When I visit the activation page with token "used-token"
    Then I am redirected to "/login?activation=already_activated"

  Scenario: Server error redirects with failed
    Given the activation API returns 500
    When I visit the activation page with token "broken-token"
    Then I am redirected to "/login?activation=failed"

  Scenario: Loader is visible while activation is in flight
    Given the activation API returns 200 after a delay
    When I visit the activation page early with token "slow-token"
    Then the activation loader is visible
    And I am redirected to "/login?activation=success"
