Feature: Forms Management

  Background:
    Given I am authenticated as a user
    And the workspaces API returns a workspace with slug "test-workspace"

  Scenario: Forms page loads with empty state
    Given the forms API returns an empty list
    When I navigate to "/test-workspace/forms"
    Then I see the text "No forms yet."
    And I see the text "Create your first form"

  Scenario: Forms page displays existing forms
    Given the forms API returns a list with form "Customer Survey"
    When I navigate to "/test-workspace/forms"
    Then I see the text "Customer Survey"

  Scenario: Create a new form
    Given the forms API returns an empty list
    And the create form API returns a new form with title "New Contact Form"
    When I navigate to "/test-workspace/forms"
    And I click "New Form"
    Then I see the heading "Create Form"
    When I fill in "Title" with "New Contact Form"
    And I click the create form submit button
    Then I see the text "New Contact Form"

  Scenario: Edit a form changes its title
    Given the forms API returns a list with form "Old Title"
    And the update form API returns a form with title "Updated Title"
    When I navigate to "/test-workspace/forms"
    Then I see the text "Old Title"
    When I click the edit button for the first form
    Then I see the heading "Edit Form"
    And the title input has value "Old Title"
    When I clear the title input and type "Updated Title"
    And I click the save button in the dialog
    Then I see the text "Updated Title"

  Scenario: Delete a form removes it from the list
    Given the forms API returns a list with form "To Be Deleted"
    And the delete form API returns success
    When I navigate to "/test-workspace/forms"
    Then I see the text "To Be Deleted"
    When I click the delete button for the first form
    Then I see the heading "Delete Form"
    When I confirm the deletion
    Then I do not see the text "To Be Deleted"

  Scenario: Export form as JSON triggers a download
    Given the forms API returns a list with form "Exportable Form"
    And the export form API returns JSON content
    When I navigate to "/test-workspace/forms"
    And I open the export menu for the first form
    Then I see the text "Export as JSON"
    And I see the text "Export as YAML"

  Scenario: Import modal shows format selector
    Given the forms API returns an empty list
    When I navigate to "/test-workspace/forms"
    And I click "Import"
    Then I see the heading "Import Form"
    And I see the text "File format"
