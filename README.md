# Date Recur (Drupal 8)

Recurring dates, i.e. date repeat, for Drupal 8.

Provides a new field type that supports repeating dates via repeat rules (RRULE). For RRULE compilation, [php-rrule](https://github.com/rlanvin/php-rrule) is used.

## Status

Depends on Drupal 8.2, at the time of commit requiring a core patch
https://www.drupal.org/node/2161337#comment-11452257, which is about to be
committed

This is still quite rough. Most prominently, it misses a UI to create repeat
rules. The basic architecture is solid, though. It uses a seperate table per
field to store the repeat occurences to not automatically always load them with
the entity. If there are many (think hundreds or thousands) occurences, this
should preserve sanity.

The formatter just displays all occurences at the moment and should be
considered a stub, as should be the widget.

Views works. Calendar needs to be patched (patch follows).

## Installation

Installation via composer is recommended. As long as the project lives in a sandbox, add the following to your composer.json:

    {
        "type": "package",
        "package": {
            "name": "drupal/date_recur",
            "version": "8.0.0",
            "type": "drupal-module",
            "source": {
                "url": "https://git.drupal.org:sandbox/frando/2775015.git",
                "type": "git",
                "reference": "refs/heads/8.x-1.x"
            }
        }
    }

And then:

    composer require drupal/adminimal_admin_toolbar --prefer-source

