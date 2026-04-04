# cl-community-stats

Presentation adapter for community market stats.

## What This Plugin Does
- Registers the Community Stats Bricks element.
- Requests canonical stats from `cl-reso-link`.
- Renders minimal SSR stat cards (`value` + `label`) from engine values.

## Required Inputs
- `community_key` (required)

Optional:
- `months` (default `12`)

## Dependency on cl-reso-link
- Endpoint: `/wp-json/cl-reso-link/v1/stats/community`
- Canonical schema/contract authority: `../cl-reso-link/docs/*`

## Unique Behavior
- Fail-soft output (missing/invalid stats are hidden)
- No empty stats container when no stat values exist
- No client-side query construction
- No MLS interpretation, local aggregation, or local calculations
