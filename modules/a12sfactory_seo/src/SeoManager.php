<?php

namespace Drupal\a12sfactory_seo;

class SeoManager {

  /**
   * By default, Drupal uses all entity "links" to generate the HTML links
   * tags in the header.
   * But some of them are useless, some leads to 403 errors, some must be
   * restricted to authenticated users, ...
   *
   * So this method will return an hardcoded list of useless rel attributes.
   *
   * For more SEO explanation, @see https://www.flocondetoile.fr/blog/maitriser-les-entetes-de-drupal-8-et-son-seo
   * For Drupal related issue, @see https://www.drupal.org/project/drupal/issues/2821635,
   * @see https://www.drupal.org/project/drupal/issues/2406533 and related issues.
   *
   * @return array
   */
  public function getUselessRelAttributes(): array {
    return [
      'delete-form',
      'delete-multiple-form',
      'edit-form',
      'version-history',
      'revision',
      'create'
    ];
  }

}
