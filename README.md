# cl-community-stats

## Purpose
Community market-stat presentation adapter for Bricks.

## Inputs
- Builder controls:
  - `community_key_input`
  - `months`
- Runtime fallback:
  - legacy saved `community_key`

## Output
- SSR stat cards rendered from canonical community stats

## Dependencies
- `cl-reso-link`
- `/wp-json/cl-reso-link/v1/stats/community`

## Known Constraints
- `community_key_input` is the builder-facing key and falls back to legacy `community_key`
- dynamic community values are resolved with Bricks-native `render_dynamic_data()`
- `months` remains a plugin-local numeric input and is not part of the community-key standardization path
- the plugin hides missing metrics instead of inventing replacements
