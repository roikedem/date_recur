Date Recur

Provides a recurring/repeating dates field.

# Features

 * Provides a new field type that supports repeating dates via recurrence rules
   (RRule). For RRule compilation, [php-rrule][rlanvin-php-rrule] is used.
 * Provides a simple formatter that can display the next occurrences and the
   repeat rule in human readable form. First start of human readable display
   with support for Drupal-based localization is there. Falls back to
   php-rrule's human-readable generator.
 * Timezones are handled, including daylight saving time back-conversion
   (a weekly event, starting 8pm, created in summer, should be at 8pm in 
   winter too).
 * Views integration is provided and works.

Functionality is there and basically works. Misses testing and tests. Several 
edge cases are not yet covered.

# License

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software 
Foundation; either version 2 of the License, or (at your option) any later 
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with 
this program; if not, write to the Free Software Foundation, Inc., 51 Franklin 
Street, Fifth Floor, Boston, MA 02110-1301 USA.

## Status and implementation notes

 * Depends on Drupal 8.2 and the experimental _Datetime Range_ module.
 * The field type uses a separate table per field to store the repeat
   occurrences to not automatically always load them with the entity. If there
   are many (think hundreds or thousands) occurrences, this should preserve
   sanity. Currently, the that table is only used for views. Likely this should
   also be used to display the next occurrences (instead of, as of now,
   calculating them from the repeat rule).
 * Calendar needs to be patched to make views created from calendar templates
   support recurring date fields (see https://www.drupal.org/node/2820803).
 * Both the RRULE calculation and the storage layer for individual occurences
   are pluggable to ease further developments.
 * **The update from alpha 2 to alpha 3 includes a data model change without 
   an upgrade path. Either re-create the field or read the release notes on how
   to update manually**

## Installation

Installation via composer is recommended. Run the following from the project root:

    composer require drupal/date_recur:dev-1.x --prefer-source

Installation without composer is unsupported. If the command fails, make sure your setup is prepared to install Drupal modules via composer: Either by starting your site from [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project), or by following the [instructions to edit your site's composer.json](https://www.drupal.org/node/2718229#managing-contributed) to define drupal.org as a package source and to define the install directories for Drupal modules. Also, make sure that git is installed, as this is required to install development snapshots via composer.

## Usage

Enable the module and add a new field of type "Date recur". Create content as usual. Build views the same way as you would build regular date views.

  [rlanvin-php-rrule]: https://github.com/rlanvin/php-rrule
