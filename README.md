# cl-community-stats

## Purpose
Community statistics presentation adapter for `cl-reso-link`.

## Architecture (Pass 1)
- SSR-first Bricks rendering through a shared presenter: `includes/class-community-stats-presenter.php`
- Presentation plugin only: no MLS/statistical calculations are performed here
- Canonical stats authority remains `cl-reso-link`

## Bricks Controls
- `geo_shape_id_input` (preferred)
- `community_input` (fallback)
- `community_key_input` (legacy fallback)
- legacy saved `community_key` (compatibility fallback)
- `months` (`1..60`, default `12`)
- `metrics` (multi-select)
- `display_mode` (`cards`, `inline`, `list`)
- `empty_state` (`hide`, `message`)

## Context Precedence
`geo_shape_id_input` -> `community_input` -> `community_key_input` -> legacy `community_key`

Only the highest-precedence valid context param is sent to:
`GET /wp-json/cl-reso-link/v1/stats/community`

## Initial Metric Allowlist
- `median_list_price`
- `median_sale_price`
- `sale_to_list_ratio`
- `months_of_inventory`
- `active_listing_count`
- `closed_sales_count`

## Display Output
Stable class-based SSR markup:
- `.cl-community-stats`
- `.cl-community-stats--cards`
- `.cl-community-stats--inline`
- `.cl-community-stats--list`
- `.cl-community-stats__item`
- `.cl-community-stats__value`
- `.cl-community-stats__label`
- `.cl-community-stats__empty`

## Formatting
Presentation-only formatting:
- price metrics as currency
- `sale_to_list_ratio` as percentage
- `months_of_inventory` as decimal
- counts as formatted integers

Missing/null metrics are hidden; values are never invented.

## Planned Pass 2
- Shortcodes (`[cl_community_stats]` and `[cl_community_stat]`) are planned for pass 2 and are not implemented in pass 1.
