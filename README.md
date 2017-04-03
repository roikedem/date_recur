# Date Recur (Drupal 8)

Recurring dates, i.e. date repeat, for Drupal 8.

* Provides a new field type that supports repeating dates via recurrence rules (RRule). For RRule compilation, [php-rrule](https://github.com/rlanvin/php-rrule) is used.
* Provides an interactive widget featuring a dynamic repeat rule entry form. Makes use of [rrule.js](https://github.com/jkbrzt/rrule/) which is included with the module.
* Provides a simple formatter that can display the next occurrences and the repeat rule in human readable form. First start of human readable display with support for Drupal-based localization is there. Falls back to php-rrule's human-readable generator.
* Timezones are handled, including daylight saving time back-conversion (a weekly event, starting 8pm, created in summer, should be at 8pm in winter too).
* Views integration is provided and works.

Functionality is there and basically works. Misses testing and tests. Several edge cases are not yet covered.

**This is an alpha release. Do not use in production. Until beta, data model changes may occur without an upgrade path.** This means that the field may have to be deleted and recreated.

_Funding for further development is welcome. Contact me via the [contact form](https://www.drupal.org/user/21850/contact) in case you are interested._

## Status and implementation notes

* Depends on Drupal 8.2 and the experimental _Datetime Range_ module.
* The field type uses a separate table per field to store the repeat occurrences to not automatically always load them with the entity. If there are many (think hundreds or thousands) occurrences, this should preserve sanity. Currently, the that table is only used for views. Likely this should also be used to display the next occurrences (instead of, as of now, calculating them from the repeat rule).
* Calendar needs to be patched to make views created from calendar templates support recurring date fields (see https://www.drupal.org/node/2820803).
* Both the RRULE calculation and the storage layer for individual occurences are pluggable to ease further developments.
* **The update from alpha 2 to alpha 3 includes a data model change without an upgrade path. Either re-create the field or read the release notes on how to update manually**

## Installation

Installation via composer is recommended. Run the following from the project root:

    composer require drupal/date_recur:dev-1.x --prefer-source

Installation without composer is unsupported. If the command fails, make sure your setup is prepared to install Drupal modules via composer: Either by starting your site from [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project), or by following the [instructions to edit your site's composer.json](https://www.drupal.org/node/2718229#managing-contributed) to define drupal.org as a package source and to define the install directories for Drupal modules. Also, make sure that git is installed, as this is required to install development snapshots via composer.

## Usage

Enable the module and add a new field of type "Date recur". You likely want to select the "Date recur interactive widget" as widget. Create content as usual. Build views the same way as you would build regular date views.