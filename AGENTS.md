# cl-community-stats

## Documentation Scope

This document is plugin-specific behavior guidance.
Authoritative API contracts and canonical field definitions are maintained in `cl-reso-link/docs/*`.

## Purpose

cl-community-stats is a presentation-layer adapter for community-level market statistics.

It consumes canonical data from `cl-reso-link` and renders formatted output for use in Bricks templates.

---

## Responsibilities

This plugin is responsible for:

- Fetching community statistics from:
  `/wp-json/cl-reso-link/v1/stats/community`

- Formatting values for UI display:
  - currency (compact)
  - percentage (1 decimal)
  - numeric (1 decimal)

- Rendering structured HTML for Bricks elements

---

## Non-Responsibilities

This plugin MUST NOT:

- Perform statistical calculations
- Modify or reinterpret MLS data
- Cache or persist data
- Define or alter API contracts
- Contain business logic
- Depend on URL structure or post slug

---

## Data Contract

Input:
- `community_key` (required)
- `months` (optional, default: 12)

Output:
- Median Sale Price
- Months of Inventory
- Sale-to-List Ratio

Values are sourced directly from:
`cl-reso-link` canonical stats endpoint.

---

## Error Handling

- Failed requests return empty stat values
- No placeholder text is rendered
- Labels remain visible

---

## Integration Rules

- Must receive `community_key` from template
- Templates must not pass `{post_slug}`
- Templates must not construct queries
- Templates must bind via:
  `{acf:community_key}`

---

## Architecture Position

cl-community-stats is a consumer plugin.

It sits between:

Template → cl-community-stats → cl-reso-link

It does not communicate with MLS or external APIs directly.

---

## Future Extensions

Permitted:
- Additional display fields
- Field selection controls
- Layout variants

Not permitted:
- Derived metrics
- Trend calculations
- Data aggregation logic
