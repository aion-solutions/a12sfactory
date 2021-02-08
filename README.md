CONTENTS OF THIS FILE
---------------------

 * Distribution
 * Requirements
 * Background styles


DISTRIBUTION
------------

The A12S Factory distribution provides advanced features for content management. It solves some
common issues with a huge use of Paragraphs.


REQUIREMENTS
------------

The distribution relies heavily on Bootstrap 4, to handle the grid feature through paragraph behaviors. This may evolve
if there are some interests for other framework, with people able to collaborate on such issue.


PARAGRAPH BACKGROUND STYLES
---------------------------

Enter one value per line, in the format `key|label` where `key` is the CSS class name (without the .), and `label` is the
human readable name of the style in administration forms.

These styles are defined and can be customized in `background-styles` library that is defined in `a12sfactory.libraries.yml`.

To customize the styles to fit your brand with your own theme, process as follow:

1. Copy the CSS (`a12sfactory/css/theme/background-styles.theme.css`) files to your own theme.
2. Override or replace the `background-styles` library in your own frontend theme. You will need to edit
   `YOURTHEME.libraries.yml` and `YOURTHEME.info.yml`. Refer to the
   [documentation manual for overriding libraries in your theme](https://www.drupal.org/docs/8/theming-drupal-8/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-theme#override-extend)
   for more details.
3. Edit the CSS file in your own theme to customize the styles as you wish. You will notice that the admin form will
   load your styles in the available "Background style" options for Paragraphs.


