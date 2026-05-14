# cl-community-stats

## Purpose
Community statistics presentation adapter for `cl-reso-link`.

## Architecture
- SSR-first rendering for Bricks and shortcodes via shared presenter: `includes/class-community-stats-presenter.php`
- Presentation-only plugin: no MLS/statistical calculations are performed here
- Canonical stats authority remains `cl-reso-link`

## Context Inputs and Precedence
Accepted context inputs:
- `geo_shape_id` / `geo_shape_id_input` (preferred)
- `community` / `community_input` (fallback)
- `community_key` / `community_key_input` (legacy fallback)

Resolution precedence is strict:
`geo_shape_id -> community -> community_key`

Only the highest-precedence valid context param is sent to:
`GET /wp-json/cl-reso-link/v1/stats/community`

## Metric Allowlist
- `median_list_price`
- `median_sale_price`
- `sale_to_list_ratio`
- `months_of_inventory`
- `active_listing_count`
- `closed_sales_count`

## Bricks Controls
- `geo_shape_id_input`
- `community_input`
- `community_key_input` (legacy saved `community_key` still supported)
- `months` (`1..60`, default `12`)
- `metrics` (multi-select)
- `display_mode`: `cards`, `inline`, `list`, `single`
- `metric` (single-mode metric)
- `show_label` (`true`/`false`)
- `number_format` (`default`/`compact`)
- `empty_state` (`hide`/`message`)

## Output Classes
Grouped modes:
- `.cl-community-stats`
- `.cl-community-stats--cards`
- `.cl-community-stats--inline`
- `.cl-community-stats--list`
- `.cl-community-stats__item`
- `.cl-community-stats__value`
- `.cl-community-stats__label`
- `.cl-community-stats__empty`

Single metric mode:
- `.cl-community-stat`
- `.cl-community-stat--{metric-slug}`
- `.cl-community-stat__value`
- `.cl-community-stat__label`

## Shortcodes
Grouped stats:
```text
[cl_community_stats geo_shape_id="mount_pleasant" metrics="median_sale_price,months_of_inventory"]
```

Single stat:
```text
[cl_community_stat geo_shape_id="mount_pleasant" metric="median_sale_price"]
```

Single stat value-only:
```text
[cl_community_stat geo_shape_id="mount_pleasant" metric="median_sale_price" output="value"]
```

Optional shortcode attrs:
- `months` (default `12`)
- `empty_state` (`hide`/`message`)
- `show_label` (`true`/`false`, single mode)
- `number_format` (`default`/`compact`; price metrics only)
- `display_mode` (`cards`/`inline`/`list`/`single`, grouped shortcode)
- `metric` (for grouped shortcode when `display_mode="single"`)
- `output` (`full`/`value`; `value` applies to single-stat rendering)

## Formatting
Presentation-only formatting:
- price metrics as currency (`default`) or compact currency (`compact`, e.g. `$858k`, `$1.4m`)
- `sale_to_list_ratio` as percentage
- `months_of_inventory` as decimal
- counts as formatted integers

Missing/null metrics are hidden. Values are never invented.
