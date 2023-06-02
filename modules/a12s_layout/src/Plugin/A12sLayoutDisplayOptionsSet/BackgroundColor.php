<?php

namespace Drupal\a12s_layout\Plugin\A12sLayoutDisplayOptionsSet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\a12s_layout\DisplayOptions\DisplayOptionsSetPluginBase;

/**
 * Plugin implementation of Display Options Set for Background Color.
 *
 * @A12sLayoutDisplayOptionsSet(
 *   id = "background_color",
 *   label = @Translation("Background color"),
 *   description = @Translation("Provides options for background color."),
 *   category = @Translation("Background"),
 *   applies_to = {"layout", "paragraph"},
 *   target_template = "paragraph"
 * )
 */
class BackgroundColor extends DisplayOptionsSetPluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [
      'colors' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {
    // @todo improve the code.
    if (!empty($configuration['background_color'])) {
      if (!in_array($configuration['background_color'], $variables['attributes']['class'])) {
        $variables['attributes']['class'][] = $configuration['background_color'];
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function globalSettingsForm(array &$form, FormStateInterface $formState, array $config = []): void {
    $default = $this->mergeConfigWithDefaults($config);

    $form['colors'] = [
      '#type' => 'textarea',
      '#default_value' => $this->keyValue2Text($default['colors']),
      '#title' => $this->t('Available CSS classes for @property', ['@property' => 'background colors']),
      '#description' => $this->t('Enter one value per line, in the format <b>key|label</b> where <em>key</em> is the CSS class name (without the .), and <em>label</em> is the human readable name of the option in administration forms.'),
      '#cols' => 60,
      '#rows' => 10,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function validateGlobalSettingsForm(array $form, FormStateInterface $formState) {
    $backgroundColors = $this->text2KeyValue($formState->getValue('colors', ''));

    if (!$this->validateKeyValue($backgroundColors)) {
      $formState->setError($form['colors'], $this->t('Each key must be a string at most 255 characters long.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitGlobalSettingsForm(array $form, FormStateInterface $formState) {
    $backgroundColors = $this->text2KeyValue($formState->getValue('colors', ''));
    $formState->setValue('colors', $backgroundColors);
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $formState, array $values = [], array $parents = []): array {
    if (!empty($this->globalConfiguration['colors'])) {
      $form['#type'] = 'container';

      $form['background_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Background color'),
        '#options' => $this->globalConfiguration['colors'],
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $values['background_color'] ?? '',
      ];
    }

    return $form;
  }

  /**
   * Validates key for a list of key/value pairs.
   *
   * @param array $array
   *   The array to validate.
   *
   * @return bool
   *   Whether the pairs are valid or not.
   */
  protected function validateKeyValue(array $array): bool {
    foreach (array_keys($array) as $key) {
      if (strlen($key) > 255) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Convert array values to a string to be used in a textarea.
   *
   * @param array $values
   *   The values list.
   *
   * @return string
   */
  protected function keyValue2Text(array $values): string {
    $text = array_reduce(array_keys($values), function(string $carry , string $key) use ($values) {
      return $carry . $key . '|' . $values[$key] . PHP_EOL;
    }, '');
    return rtrim($text, PHP_EOL);
  }

  /**
   * Convert a string containing a list of options to a keyed array.
   *
   * @param string $string
   *   The string should be a list of key|value pairs separated by a line break.
   *
   * @return array
   */
  protected function text2KeyValue($string): array {
    $matches = [];
    if (preg_match_all('/\s*(?P<key>[^\|\s]+)\s*\|\s*(?P<value>[^\r\n]+)/m', $string, $matches)) {
      return array_combine($matches['key'], $matches['value']);
    }

    return [];
  }

}
