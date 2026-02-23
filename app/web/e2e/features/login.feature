Feature: Login

  Background:
    Given I navigate to "/login"

  Scenario: Login form renders correctly
    Then I see the heading "Welcome back!"
    And the "Email" field is visible
    And the "Password" field is visible
    And the "Sign In" button is visible

  Scenario: Validation errors on empty form submit
    When I click "Sign In"
    Then I see field error "Please enter your email address"
    And I see field error "Please enter your password"

  Scenario: Invalid credentials shows an error alert
    Given the login API returns 401 with code "invalid_credentials"
    When I submit the login form with email "user@example.com" and password "wrongpassword"
    Then I see an alert containing "Authentication Failed"
    And I see an alert containing "Invalid email or password."

  Scenario: Unactivated account shows an error alert
    Given the login API returns 401 with code "account_not_activated"
    When I submit the login form with email "user@example.com" and password "password123"
    Then I see an alert containing "Authentication Failed"
    And I see an alert containing "Account is not activated. Please check your email."

  Scenario: Successful login shows a success alert and redirects to home
    Given the login API returns 200
    When I submit the login form with email "user@example.com" and password "password123"
    Then I see an alert containing "Access Granted"
    And I am redirected to "/home"

  Scenario: Sign Up button navigates to the register page
    When I click "Sign Up"
    Then the page URL is "/register"

  Scenario: Success alert when arriving from a successful activation
    Given I navigate to "/login?activation=success"
    Then I see an alert containing "Account activated successfully."

  Scenario: Info alert when account was already activated
    Given I navigate to "/login?activation=already_activated"
    Then I see an alert containing "Account already activated"
    And I see an alert containing "Your account has already been activated. You can sign in now."

  Scenario: Error alert when activation link has expired
    Given I navigate to "/login?activation=failed"
    Then I see an alert containing "Activation link is invalid or has expired."
    And I see an alert containing "The link may have already been used or has expired. Please register again."
