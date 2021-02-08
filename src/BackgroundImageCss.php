<?php

namespace Drupal\a12sfactory;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;

/**
 * Create inline CSS for responsive background images.
 *
 * @todo provide UI for default settings.
 */
class BackgroundImageCss {

  /**
   * Build the CSS code for the given file URI.
   *
   * @param string $uri
   *   The image URI.
   * @param array $settings
   *   The settings to use (breakpoints, image styles...).
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function css(string $uri, array $settings = []): MarkupInterface {
    $css = [];

    // No selector means that we do not define a default background image.
    if (!empty($settings['selector'])) {
      $url = NULL;

      if (!empty($settings['style'])) {
        $image_style = ImageStyle::load($settings['style']);

        if ($image_style) {
          $url = $image_style->buildUrl($uri);
        }
      }
      else {
        $url = file_create_url($uri);
      }

      if ($url) {
        $css[] = $settings['selector'] . ' { background-image: url("' . $url . '"); }';
      }
    }

    if ($settings['breakpoint_group'] && !empty($settings['breakpoints'])) {
      $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($settings['breakpoint_group']);

      foreach ($settings['breakpoints'] as $breakpoint => $breakpoint_settings) {
        if (!isset($breakpoints[$breakpoint])) {
          continue;
        }

        if (empty($breakpoint_settings['style']) && empty($breakpoint_settings['selector'])) {
          continue;
        }

        $selector = $breakpoint_settings['selector'] ?? $settings['selector'];
        $style = $breakpoint_settings['style'] ?? $settings['style'];
        $url = NULL;

        if ($style) {
          $image_style = ImageStyle::load($style);

          if ($image_style) {
            $url = ImageStyle::load($style)->buildUrl($uri);
          }
        }
        else {
          $url = file_create_url($uri);
        }

        if ($url) {
          $query = $breakpoints[$breakpoint]->getMediaQuery();

          if ($query != '') {
            $css[] = '@media ' . $query . ' { ';
          }

          // @todo something to deal with multipliers?

          $css[] = $selector . ' { background-image: url("' . file_url_transform_relative($url) . '"); }';

          if ($query != '') {
            $css[] = '}';
          }
        }
      }
    }

    return Markup::create(implode("\n", $css));
  }

  /**
   * Get the HTML Head content for a background image CSS.
   *
   * @param string $uri
   * @param array $settings
   *
   * @return array
   *   An array ready to attach to "html_head".
   */
  public function htmlHead(string $uri, array $settings = []): array {
    if (!isset($settings['id'])) {
      $settings['id'] = Html::getUniqueId('background-image-css');
    }

    $css = $this->css($uri, $settings);
    return [
      ['#tag' => 'style', '#value' => $css],
      $settings['id'],
    ];
  }

}
