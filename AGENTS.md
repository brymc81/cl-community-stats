# cl-community-stats

## Purpose

`cl-community-stats` is the statistics presentation adapter for Charleston Livability.

It consumes canonical statistics from `cl-reso-link` and renders SSR-first output for Bricks templates, shortcodes, and future statistics display components.

This plugin is the home for statistics presentation. It is not the statistics engine.

---

## Authority

`cl-reso-link/docs/*` owns:

- canonical stats endpoint contracts
- canonical metric names
- metric definitions
- MLS/statistical calculations
- geography resolution
- `geo_shape_id`, `community`, and `community_key` semantics
- data normalization
- error semantics

`cl-community-stats` must not redefine those contracts.

---

## Architecture Position

Canonical flow:

```text
Bricks template / shortcode
→ cl-community-stats
→ cl-reso-link stats endpoint
→ canonical stats response
→ SSR presentation output
```

`cl-community-stats` may format and render values for display, but it must not compute market statistics from MLS data.

---

## Responsibilities

This plugin is responsible for:

- Resolving builder or shortcode inputs
- Preferring `geo_shape_id_input` for community-page templates
- Preserving `community` and `community_key` only as compatibility fallbacks
- Calling `/wp-json/cl-reso-link/v1/stats/community`
- Rendering SSR statistics output
- Providing Bricks controls for metric selection and display mode
- Providing shortcodes for grouped and single-stat output
- Rendering graceful empty/error states
- Exposing stable classes that Bricks can style

---

## Non-Responsibilities

This plugin must not:

- Fetch MLS data directly
- Perform MLS/statistical calculations
- Modify, normalize, or reinterpret MLS fields
- Define metric formulas
- Define canonical API contracts
- Infer geography from URL, path, slug, post title, or frontend context
- Cache or persist canonical data unless explicitly designed later
- Invent missing statistics
- Invent fallback geography
- Silently broaden scope when context resolution fails

If statistics calculation or geography resolution is unclear, fix `cl-reso-link`, not this plugin.

---

## Context Input Standard

Preferred builder control:

```text
geo_shape_id_input
```

Fallback controls, for compatibility only:

```text
community
community_key_input
community_key
```

Resolution precedence:

```text
geo_shape_id_input
→ community
→ community_key_input
→ legacy community_key
```

Rules:

- Dynamic Bricks values must be resolved before sanitization.
- Sanitization must happen after dynamic resolution.
- Do not infer context from slug, URL, path, title, or post metadata.
- Do not send multiple competing context values unless the contract explicitly allows it.
- If no valid context is resolved, render a safe empty state.

---

## Canonical Endpoint

Primary endpoint:

```text
GET /wp-json/cl-reso-link/v1/stats/community
```

Expected params:

```text
geo_shape_id preferred
community fallback
community_key legacy fallback
months optional, default 12
```

Current engine limitation:

- `geo_shape_id` stats are community-scoped.
- Non-community shapes are not valid for `/stats/community` unless `cl-reso-link` explicitly expands that contract later.

---

## Metrics

Metric names must come from `cl-reso-link` documentation.

Initial supported display metrics:

```text
median_list_price
median_sale_price
sale_to_list_ratio
months_of_inventory
active_listing_count
closed_sales_count
```

The plugin may let users select which metrics to display, but it must not redefine formulas.

---

## Display Modes

The Bricks element may support:

```text
cards
inline
list
single
```

Shortcodes may support:

```text
[cl_community_stats geo_shape_id="..." metrics="median_sale_price,months_of_inventory"]
[cl_community_stat geo_shape_id="..." metric="median_sale_price"]
```

All display modes must render server-side.

JavaScript must not be required for baseline output.

---

## Styling Contract

This plugin should emit semantic, stable wrapper classes and let Bricks or theme CSS handle most visual design.

Suggested classes:

```text
.cl-community-stats
.cl-community-stats--cards
.cl-community-stats--inline
.cl-community-stats--list
.cl-community-stats__item
.cl-community-stats__value
.cl-community-stats__label
.cl-community-stats__meta
.cl-community-stats__empty
.cl-community-stat
.cl-community-stat--{metric-slug}
.cl-community-stat__value
.cl-community-stat__label
```

Avoid excessive inline styles.

Avoid locking the user into a single card design.

---

## Formatting

Display formatting is allowed, but must be explicit and presentation-only.

Examples:

- currency formatting for price metrics
- compact currency formatting for price metrics (e.g. `$858k`, `$1.4m`)
- percent formatting for sale-to-list ratio
- decimal formatting for months of inventory
- date formatting for `as_of`

Formatting must not change the underlying metric meaning.

Missing values should be hidden or rendered according to documented empty-state behavior. Do not invent placeholders that imply data exists.

---

## Error Handling

- Missing context renders a safe empty state.
- Failed requests render a safe empty/error state.
- Missing individual metrics are hidden unless the display mode explicitly chooses to show unavailable labels.
- Do not render fabricated values.
- Do not broaden to site-wide stats when scoped stats fail.

---

## Shortcode Rules

Shortcodes are presentation escape hatches, not separate data systems.

They must:

- Use the same request/path logic as the Bricks element
- Use the same metric allowlist
- Use the same formatting helpers
- Use the same empty/error handling
- Avoid duplicating endpoint logic

---

## Future Direction

This plugin may eventually house:

- community stats
- neighborhood stats displays
- market comparison displays
- trend/table presentation
- small chart wrappers
- stats shortcodes
- Bricks statistics elements

Future analytics or derived metrics must still be computed by `cl-reso-link` or a deliberately designed analytics layer, not casually inside this presentation plugin.

Python/math libraries are not part of the live WordPress rendering path unless a future batch analytics pipeline is explicitly designed.

---

## Development Discipline

Before changing this plugin:

1. Observe current behavior.
2. Confirm the relevant `cl-reso-link` contract.
3. Decide whether the change belongs in the engine or presentation layer.
4. Keep SSR as the baseline.
5. Keep output class-based and Bricks-styleable.
6. Avoid silent contract changes.
7. Update README/AGENTS when behavior changes.

---

## Testing Expectations

When modifying this plugin, verify:

- `geo_shape_id_input` resolves and is preferred
- `community` fallback works only when explicitly supplied
- legacy `community_key` fallback remains compatible
- missing context renders safe empty state
- selected metrics control output
- each display mode renders SSR markup
- shortcodes share the same rendering path
- no MLS/statistical calculation is introduced locally

---

## Final Rule

`cl-community-stats` presents statistics. It does not become the market-statistics brain.

If the work requires defining what a metric means, computing it from MLS data, or deciding how geography maps to listings, stop and move that work to `cl-reso-link`.
