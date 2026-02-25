Feature: Change Password

  Background:
    Given I navigate to "/change-password"

  Scenario: Change password form renders correctly
    Then I see the heading "Set new password"
    And the "New password" field is visible
    And the "Confirm password" field is visible
    And the "Set password" button is visible

  Scenario: Validation errors on empty form submit
    When I click "Set password"
    Then I see field error "Please enter your password"
    And I see field error "Please confirm your password"

  Scenario: Validation error when password is too short
    When I fill the change password form with "short" and "short"
    And I click "Set password"
    Then I see field error "Password must be at least 8 characters"

  Scenario: Validation error when passwords do not match
    When I fill the change password form with "newpassword1" and "newpassword2"
    And I click "Set password"
    Then I see field error "Passwords don't match"

  Scenario: Successful password change redirects to home
    Given the change password API returns 200
    When I fill the change password form with "newpassword1" and "newpassword1"
    And I click "Set password"
    Then I see an alert containing "Password updated"
    And I am redirected to "/home"

  Scenario: API error shows an error alert
    Given the change password API returns error with code "password_mismatch"
    When I fill the change password form with "newpassword1" and "newpassword1"
    And I click "Set password"
    Then I see an alert containing "Failed to update password"

  Scenario: Login with mustChangePassword flag redirects to change password page
    Given I navigate to "/login"
    And the login API returns 200 with mustChangePassword
    When I submit the login form with email "user@example.com" and password "temppassword"
    Then I am redirected to "/change-password"
