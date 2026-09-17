=== LightMoving UTF8MB4 Converter ===
Contributors: angelsrock
Tags: database, utf8mb4, emoji, charset, unicode
Requires at least: 5.8
Tested up to: 7.1.1
Requires PHP: 7.4
Stable tag: 1.0.28
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Safely scan and convert WordPress database tables to utf8mb4 for 4-byte character support, including emojis and multilingual Unicode characters.

== Description ==

LightMoving UTF8MB4 Converter helps modernize older WordPress databases that still use latin1, utf8, or other non-utf8mb4 collations.

The plugin scans your WordPress database tables, identifies tables that are not using utf8mb4, and provides a safe administrator-controlled conversion workflow.

Features include:

* Database charset and collation scan
* WordPress table scan using the active table prefix
* utf8mb4 server capability detection
* Backup confirmation workflow
* Required CONVERT confirmation step
* Individual WordPress table conversion
* Clean conversion success logging
* Responsive modern admin interface
* Direct Tools link from the Plugins page
* No automatic conversion on activation

This utility is especially useful for:

* older WordPress sites
* migrated websites
* legacy hosting environments
* Themify and older builder installs
* emoji and 4-byte character support warnings
* multilingual WordPress environments

== Safety Features ==

* No automatic conversion on plugin activation
* Administrator-only access
* Backup confirmation checkbox
* Required CONVERT confirmation text
* Converts only tables using the active WordPress table prefix
* Displays current database charset and collation
* Displays current table engine and collation
* Converts database default charset/collation
* Converts WordPress tables individually

== Important ==

Always create a complete database backup before converting database tables.

Large tables may require additional time depending on hosting resources. Conversion operations may temporarily lock tables while MySQL or MariaDB processes ALTER TABLE operations.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin
3. Go to Tools → LightMoving UTF8MB4 Converter
4. Review your database and table status
5. Create a full database backup
6. Confirm and run the conversion

== Frequently Asked Questions ==

= Does this run automatically? =

No. The plugin never converts database tables automatically on activation.

= Does this support emojis? =

Yes. utf8mb4 adds support for 4-byte Unicode characters including emojis.

= Will this convert all tables? =

The plugin converts WordPress tables using the currently active WordPress table prefix.

= Should I create a backup first? =

Yes. Always create a complete database backup before running database conversion operations.

== Changelog ==
= 1.0.28 =
* Update WordPress Version

= 1.0.27 =
* Version Improvements and Compatibility

= 1.0.26 =
* Change: Clean-up & Compatibility
* Framework Responsiveness

= 1.0.25 =
* Performance Update

= 1.0.24 =
* Updated for WordPress 7.1

= 1.0.23 =
* Updated for WordPress 7.3

= 1.0.22 =
* Update data file

= 1.0.21 =
* Polished WordPress Compatibility

= 1.0.20 =
* Minor compatibility improvements

= 1.0.19 =
* Updates to changelog


= 1.0.18 =
* WordPress 7.2 compatibility


= 1.0.17 =
* Updated WordPress.org release


= 1.0.16 =
* Updated plugin for WordPress 7.0.1


= 1.0.13 =
* Renamed plugin to LightMoving UTF8MB4 Converter for improved WordPress.org name distinctiveness
* Updated plugin slug, text domain, headers, and language template references


= 1.0.11 =
* Added targeted PHPCS disable/enable documentation around intentional ALTER DATABASE and ALTER TABLE conversion queries
* Further reduced Plugin Check warnings for required database conversion operations


= 1.0.10 =
* Added properly placed translator comments for placeholder strings
* Finalized Plugin Check cleanup for internationalization messages

= 1.0.9 =
* Removed discouraged set_time_limit usage
* Added translator comments for placeholder strings
* Added PHPCS documentation for intentional database metadata and conversion queries
* Reduced readme tags to meet WordPress.org tag limits


= 1.0.8 =
* Restored package from the last confirmed working runtime build
* Added translation-ready POT language template
* Added GitHub README.md documentation
* Improved WordPress.org readme formatting and compatibility
* Added contributor metadata for WordPress.org standards

= 1.0.4 =
* Added direct Tools link on the WordPress Plugins page
* Improved plugin discoverability after activation

= 1.0.3 =
* Added polished full-width success banner after completed conversion
* Improved conversion log styling and readability
* Removed redundant post-conversion success notice output

= 1.0.2 =
* Fixed post-conversion output so successful table conversions render as one success notice and one conversion log card
* Prevented one WordPress success notice from being printed for each converted table

= 1.0.1 =
* Improved post-conversion success output with a single clean conversion log summary
* Reduced stacked admin notices after successful table conversion

= 1.0.0 =
* Initial release
* Added database utf8mb4 support scan
* Added WordPress table collation scan
* Added manual backup confirmation workflow
* Added database default charset/collation conversion
* Added individual WordPress table conversion

== Upgrade Notice ==

= 1.0.8 =
Adds translation-ready language template and documentation while preserving the last confirmed working conversion runtime.