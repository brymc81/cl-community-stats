# cl-community-stats

## Documentation Scope

This document is plugin-specific behavior guidance.
Authoritative API contracts and canonical field definitions are maintained in `cl-reso-link/docs/*`.

## Purpose

cl-community-stats is a presentation-layer adapter for community-level market statistics.

It consumes canonical data from `cl-reso-link` and renders SSR output for Bricks templates.

---

## Responsibilities

This plugin is responsible for:

- Fetching community statistics from:
  `/wp-json/cl-reso-link/v1/stats/community`

- Rendering minimal SSR stat cards (`value` + `label`) for Bricks elements

---

## Non-Responsibilities

This plugin MUST NOT:

- Perform statistical calculations
- Modify or reinterpret MLS data
- Cache or persist data
- Define or alter API contracts
- Contain business logic
- Depend on URL structure or post slug
- Reshape canonical response payloads in UI templates
- Perform ad-hoc value formatting in UI component markup

---

## Data Contract

Input:
- `community_key` (required)
- `months` (optional, default: 12)

Output:
- `median_sale_price`
- `months_of_inventory`
- `sale_to_list_ratio`

Values are sourced directly from:
`cl-reso-link` canonical stats endpoint.

---

## Error Handling

- Failed requests render no stat output
- Missing values are hidden per-stat
- No placeholder text or empty stats container is rendered

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
- UI-template formatting logic without an explicit formatting layer
