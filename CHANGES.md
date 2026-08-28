# Changelog

## [2.1.0] - 2026-08-28
### Added
- CLI scripts `cli/check_cpf_migration.php` (read-only diagnostic) and
  `cli/migrate_cpf.php` to migrate CPF data from a legacy profile field
  into a `brcpf` field, normalizing values to digits only.
- README section (English and Portuguese) documenting how to use the
  migration CLI scripts.

## [2.0.1] - 2026-08-28
### Changed
- Renamed plugin component from `profilefield_cpf` to `profilefield_brcpf`.
### Fixed
- Removed extra whitespace in a privacy metadata language string.
- Fixed remaining Moodle coding style (phpcs) violations.

## [2.0.0] - 2026-05-04
### Added
- GPL license headers and full PHPDoc across all PHP files.
- Privacy API implementation (`classes/privacy/provider.php`, null
  provider, required since Moodle 4.5).
- `privacy:metadata` language strings (en, pt_br).
- `$plugin->supported` compatibility matrix for Moodle 4.5 through 5.2.
- `display_data()` to show the formatted CPF (`XXX.XXX.XXX-XX`) on the
  profile page.
- `edit_field_set_data()` to pre-format the value when opening the edit
  form.
### Changed
- Corrected `@package` from `cpf_text`/`cpf` to `profilefield_cpf`.
- Renamed helper methods to snake_case (`normalizecpf` -> `normalize_cpf`,
  etc).
- Renamed `is_unique()` to `cpf_is_unique()` to avoid clashing with the
  base class's public method.
### Fixed
- CPF validation order: the format is now validated before the uniqueness
  check (previously uniqueness was checked first).

## [Legacy] - 2014 - 2026-02-11
- Original plugin `profilefield_cpf`, compatible with Moodle 2.2+, later
  updated for Moodle 4.5+ compatibility (PHP 8 warnings, deprecated APIs).
- Forked from [moodle-profilefield_cpf](https://github.com/willianmano/moodle-profilefield_cpf).
