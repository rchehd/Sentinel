Feature: Admin Users

  Background:
    Given the app is configured
    And I am authenticated as a user
    And the admin users API returns an empty list
    And I navigate to "/admin/users"

  Scenario: Admin users page renders correctly
    Then I see the heading "Users"
    And the "Email" field is visible
    And the "Username" field is visible
    And the "Generate password" button is visible
    And the "Create user" button is visible

  Scenario: Checkbox for requiring password change is visible
    Then the "Require password change on first login" checkbox is visible

  Scenario: Validation errors on empty form submit
    When I click "Create user"
    Then I see field error "Please enter your email address"
    And I see field error "Please choose a username"

  Scenario: Generate password button fills the password field
    When I click "Generate password"
    Then the password field is not empty

  Scenario: No users message shown when list is empty
    Then I see the text "No users yet."

  Scenario: Users table is populated when users exist
    Given the admin users API returns a list with one user
    And I navigate to "/admin/users"
    Then I see the text "admin@example.com"

  Scenario: Successful user creation shows success alert
    Given the admin users API returns 201 without generated password
    When I fill the create user form with email "new@example.com" and username "newuser"
    And I click "Create user"
    Then I see an alert containing "User created"

  Scenario: User creation with generated password shows password alert
    Given the admin users API returns 201 with generated password "Abc123!@#defGHI4"
    When I fill the create user form with email "new@example.com" and username "newuser"
    And I click "Create user"
    Then I see an alert containing "Generated password"
    And I see the text "Abc123!@#defGHI4"
