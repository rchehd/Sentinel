# Forms Architecture

## Overview

Forms are the core product. A form is a multi-stage document with conditional navigation, built from a fixed set of typed elements. Forms can also function as quizzes by assigning scores and correct answers to elements.

The form schema is stored as a **JSONB document** — always loaded and saved as a whole. This makes revisions, export/import, and future real-time collaboration straightforward.

---

## Entity Model

```
Workspace
  └── Form (metadata + status)
       └── FormRevision (immutable JSONB schema snapshot)
            └── Submission (answers JSONB, bound to revision)
```

### Form

Holds metadata and status. Does not store the schema directly.

| Field | Type | Notes |
|-------|------|-------|
| `id` | UUID | PK |
| `workspace` | FK → Workspace | Tenant isolation |
| `title` | string | Display name |
| `status` | enum | `draft`, `published`, `archived` |
| `currentRevision` | FK → FormRevision | The active revision |
| `createdBy` | FK → User | Author |
| `createdAt` | datetime | |
| `updatedAt` | datetime | |

### FormRevision

Immutable snapshot. Created every time the schema is saved. Submissions are permanently bound to the revision they were started on.

| Field | Type | Notes |
|-------|------|-------|
| `id` | UUID | PK |
| `form` | FK → Form | |
| `schema` | JSONB | Full form schema (see below) |
| `version` | int | Auto-incremented per form |
| `createdBy` | FK → User | Who saved this revision |
| `createdAt` | datetime | |

### Submission

One row per submission attempt. Answers are stored as JSONB keyed by element ID. Draft submissions are restored when the user returns to an unfinished form.

| Field | Type | Notes |
|-------|------|-------|
| `id` | UUID | PK |
| `form` | FK → Form | |
| `revision` | FK → FormRevision | Bound at creation time |
| `submittedBy` | FK → User (nullable) | Null for anonymous |
| `sessionToken` | string (nullable) | For anonymous draft restoration |
| `status` | enum | `draft`, `complete` |
| `answers` | JSONB | `{ "element-uuid": value }` |
| `scoreTotal` | int (nullable) | Computed at submission for quizzes |
| `createdAt` | datetime | |
| `updatedAt` | datetime | Last activity (used to expire old drafts) |

**Draft uniqueness constraint:** one draft per (form + submittedBy) for authenticated users, one draft per (form + sessionToken) for anonymous users.

---

## Schema JSONB Structure

The `schema` field on `FormRevision` holds the complete form definition.

```json
{
  "stages": [
    {
      "id": "uuid",
      "title": "Personal Info",
      "description": "Optional stage description",
      "elements": [
        {
          "id": "uuid",
          "type": "text",
          "label": "Full name",
          "placeholder": "John Doe",
          "required": true,
          "score": null,
          "correct_answer": null,
          "options": null,
          "validation": {
            "min_length": null,
            "max_length": 255,
            "pattern": null
          },
          "conditions": [
            {
              "action": "show",
              "if": { "element": "uuid", "op": "eq", "value": "yes" }
            }
          ]
        },
        {
          "id": "uuid",
          "type": "radio",
          "label": "Do you agree?",
          "required": true,
          "score": 10,
          "correct_answer": "option-uuid-yes",
          "options": [
            { "id": "option-uuid-yes", "label": "Yes" },
            { "id": "option-uuid-no",  "label": "No" }
          ],
          "conditions": []
        }
      ],
      "navigation": [
        {
          "goto": "stage-uuid-2",
          "if": { "element": "uuid", "op": "eq", "value": "option-uuid-yes" }
        },
        {
          "goto": "stage-uuid-3",
          "default": true
        }
      ]
    }
  ],
  "settings": {
    "show_progress_bar": true,
    "allow_back_navigation": true,
    "submit_button_label": "Submit"
  }
}
```

### Condition Operators

| Operator | Meaning |
|----------|---------|
| `eq` | equals |
| `neq` | not equals |
| `gt` / `lt` | greater / less than (numeric) |
| `contains` | string/array contains value |
| `empty` / `not_empty` | field is blank or filled |

Conditions can appear on:
- **Elements** — `action: show/hide` based on other element values within the same stage
- **Navigation** — `goto: stage-uuid` based on element values, evaluated at stage exit

---

## Element Types

Element types are defined in PHP code as an enum/registry — not stored in the database. This keeps the schema lean and makes adding new types a code change, not a migration.

### Built-in Types

| Type | Description |
|------|-------------|
| `text` | Single-line text input |
| `textarea` | Multi-line text |
| `email` | Email with format validation |
| `number` | Numeric input |
| `phone` | Phone number |
| `date` | Date picker |
| `radio` | Single-choice |
| `checkbox` | Multi-choice |
| `select` | Dropdown single-choice |
| `file` | File upload |
| `rating` | Star / numeric rating |
| `signature` | Drawn signature |
| `heading` | Non-input display element |
| `paragraph` | Non-input display text |

### Workspace Element Allowlist

Workspaces can restrict which element types are available to their editors. Stored as a JSONB array on the Workspace entity:

```json
{ "enabled_element_types": ["text", "email", "radio", "checkbox"] }
```

`null` means all built-in types are enabled. Paid modules can add types to the registry and unlock them per workspace via subscription tier.

---

## Revisions

A new `FormRevision` is created every time the schema is saved (not on every keystroke — on explicit save or auto-save debounce). The `Form.currentRevision` pointer is updated to the new revision.

**Key rules:**
- Revisions are **immutable** — never updated after creation
- Submissions reference the revision they started on
- If a user has a draft on revision 1 and the form moves to revision 2, the draft remains on revision 1 and is completed there
- The form results view shows submissions grouped by revision, or unified with a compatibility flag

---

## Draft Submissions

When a user starts filling a form:
1. A `Submission` with `status: draft` is created (or upserted)
2. Answers are saved on every stage transition (not every keystroke)
3. On return, the draft is loaded and the user resumes from the last completed stage
4. On final submit, `status` changes to `complete` and `scoreTotal` is computed

**Draft restoration lookup:**
- Authenticated: `WHERE form = ? AND submittedBy = ? AND status = draft`
- Anonymous: `WHERE form = ? AND sessionToken = ? AND status = draft` (token stored in browser localStorage)

Old drafts (no activity for 30 days) can be purged via a scheduled Symfony Messenger command.

---

## Quiz Scoring

Quiz mode is not a separate entity — it is activated when any element in the schema has `score > 0` and `correct_answer` set.

At submission time:
1. Iterate all elements in the revision schema
2. For each element with `correct_answer`, compare against submitted answer
3. Sum scores for correct answers → store as `Submission.scoreTotal`
4. Score percentage = `scoreTotal / maxPossibleScore * 100`

`maxPossibleScore` is computed from the schema at query time (sum of all `score` fields).

---

## Export / Import

Forms are exported as a JSON file containing:
```json
{
  "sentinel_version": "1.0",
  "exported_at": "2026-03-12T00:00:00Z",
  "form": {
    "title": "Customer Survey",
    "status": "published",
    "schema": { ... }
  }
}
```

**Import rules:**
- A new `Form` + `FormRevision` is created in the target workspace
- All element/stage IDs are regenerated (UUID v7) to avoid collisions
- `status` is reset to `draft` on import (requires explicit re-publishing)
- Submissions are **not** included in export/import

---

## API Endpoints

```
GET    /api/workspaces/{id}/forms                        List forms (paginated)
POST   /api/workspaces/{id}/forms                        Create form
GET    /api/workspaces/{id}/forms/{formId}               Get form + current schema
PATCH  /api/workspaces/{id}/forms/{formId}               Update metadata (title, status)
DELETE /api/workspaces/{id}/forms/{formId}               Delete form

PUT    /api/workspaces/{id}/forms/{formId}/schema        Save schema (creates revision)
GET    /api/workspaces/{id}/forms/{formId}/revisions     List revisions

POST   /api/forms/{formId}/submissions                   Submit (public, no auth required)
GET    /api/workspaces/{id}/forms/{formId}/submissions   List submissions (authenticated)
GET    /api/forms/{formId}/draft                         Get draft for current user/session
```

---

## Open Questions

- [ ] Should anonymous users be able to save drafts (via session token), or require login?
- [ ] Should stage navigation conditions support AND/OR logic groups, or only simple single conditions to start?
- [ ] Should the form results view unify submissions across revisions, or always show per-revision?
