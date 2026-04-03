# cl-community-stats

Presentation adapter for community market stats.

## What This Plugin Does
- Registers the Community Stats Bricks element.
- Requests canonical stats from `cl-reso-link`.
- Renders formatted SSR stat cards.

## Required Inputs
- `community_key` (required)

Optional:
- `months` (default `12`)

## Dependency on cl-reso-link
- Endpoint: `/wp-json/cl-reso-link/v1/stats/community`
- Canonical schema/contract authority: `../cl-reso-link/docs/*`

## Unique Behavior
- Fail-soft output (empty values on missing/invalid data)
- No client-side query construction
- No MLS interpretation or local aggregation
