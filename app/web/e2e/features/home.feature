Feature: Home

  Background:
    Given I navigate to "/home"

  Scenario: Welcome heading is rendered
    Then I see the heading "Welcome to Sentinel"

  Scenario: Stat cards are rendered
    Then I see the text "Forms"
    And I see the text "Submissions"
    And I see the text "Workflows"

  Scenario: Logout button navigates to login
    When I click "Log out"
    Then the page URL is "/login"
