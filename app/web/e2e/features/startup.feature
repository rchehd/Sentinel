Feature: Startup flow

  Scenario: Unconfigured app redirects to setup wizard
    Given the app is not configured
    When I navigate to "/"
    Then I am redirected to "/setup/admin"
    And I see the heading "Create admin account"

  Scenario: Setup form renders correctly
    Given the app is not configured
    When I navigate to "/setup/admin"
    Then I see the heading "Create admin account"
    And the "Email" field is visible
    And the "Username" field is visible
    And the setup password fields are visible
    And the "Create admin account" button is visible

  Scenario: Validation errors on empty form submit
    Given the app is not configured
    And I navigate to "/setup/admin"
    When I click "Create admin account"
    Then I see field error "Please enter your email address"
    And I see field error "Please choose a username"
    And I see field error "Please enter your password"

  Scenario: Password too short shows validation error
    Given the app is not configured
    And I navigate to "/setup/admin"
    When I fill the setup form with email "admin@example.com" username "admin" password "short" and confirm "short"
    And I click "Create admin account"
    Then I see field error "Password must be at least 8 characters"

  Scenario: Mismatched passwords show validation error
    Given the app is not configured
    And I navigate to "/setup/admin"
    When I fill the setup form with email "admin@example.com" username "admin" password "Password123" and confirm "Password999"
    And I click "Create admin account"
    Then I see field error "Passwords don't match"

  Scenario: Successful admin creation redirects to login
    Given the app is not configured
    And the setup admin API returns 201
    And I navigate to "/setup/admin"
    When I fill the setup form with valid credentials
    And I click "Create admin account"
    Then I am redirected to "/login"

  Scenario: Setup page is inaccessible once configured
    Given the app is configured
    When I navigate to "/setup/admin"
    Then I am redirected to "/login"

  Scenario: Sign in with admin credentials redirects to home
    Given the app is configured
    And the login API returns 200
    And I navigate to "/login"
    When I submit the login form with email "admin@example.com" and password "Password123"
    Then I see an alert containing "Access Granted"
    And I am redirected to "/test-workspace/home"
    And I see the heading "Welcome to Sentinel"

  Scenario: Sign out returns to login page
    Given the app is configured
    And I am authenticated as a user
    And the workspaces API returns a workspace with slug "test-workspace"
    And I navigate to "/test-workspace/home"
    When I click "Log out"
    Then the page URL is "/login"
