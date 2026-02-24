Feature: Register

  Background:
    Given I navigate to "/register"

  Scenario: Register form renders correctly
    Then I see the heading "Create an account"
    And the "Email" field is visible
    And the "Username" field is visible
    And the register password fields are visible
    And the "Sign Up" button is visible

  Scenario: Validation errors on empty form submit
    When I click "Sign Up"
    Then I see field error "Please enter your email address"
    And I see field error "Please choose a username"
    And I see field error "Please enter your password"
    And I see field error "Please confirm your password"

  Scenario: Error when password is too short
    When I enter password "short" in the register form
    And I click "Sign Up"
    Then I see field error "Password must be at least 8 characters"

  Scenario: Error when passwords do not match
    When I enter password "password123" in the register form
    And I enter confirm password "different456" in the register form
    And I click "Sign Up"
    Then I see field error "Passwords don't match"

  Scenario: Organization fields appear when checkbox is checked
    Then the "Company name" field is not visible
    When I check the "Create an organization" checkbox
    Then the "Company name" field is visible
    And the "Company domain" field is visible

  Scenario: Organization name is required when creating an organization
    When I check the "Create an organization" checkbox
    And I fill the register form with email "user@example.com", username "testuser", password "password123"
    And I click "Sign Up"
    Then I see field error "Please enter your organization name"

  Scenario: Successful registration navigates to the check-email page
    Given the register API returns 201
    When I fill the register form with email "newuser@example.com", username "newuser", password "password123"
    And I click "Sign Up"
    Then I am redirected to "/register/check-email"
    And I see the heading "Check your email"
    And I see the text "We sent an activation link to your email address."

  Scenario: Registration failure shows an error alert
    Given the register API returns 422 with detail "Email is already registered."
    When I fill the register form with email "taken@example.com", username "someuser", password "password123"
    And I click "Sign Up"
    Then I see an alert containing "Registration Failed"
    And I see an alert containing "Email is already registered."

  Scenario: Sign In button navigates to the login page
    When I click "Sign In"
    Then the page URL is "/login"

  Scenario: Direct access to check-email page redirects to register
    Given I navigate to "/register/check-email"
    Then the page URL is "/register"
