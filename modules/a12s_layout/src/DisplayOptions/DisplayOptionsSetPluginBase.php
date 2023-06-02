<?php

namespace Drupal\a12s_layout\DisplayOptions;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Display Options Set plugins.
 */
abstract class DisplayOptionsSetPluginBase extends PluginBase implements DisplayOptionsSetInterface {

  use StringTranslationTrait;

  /**
   * The stored global configuration for this plugin.
   *
   * @var mixed
   */
  protected mixed $globalConfiguration;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $config = $configFactory->get('a12s_layout.display_options')->get($this->getMachineName()) ?? [];
    $this->globalConfiguration = $this->mergeConfigWithDefaults($config);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DisplayOptionsSetInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritDoc}
   */
  public function defaultValues(): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function mergeConfigWithDefaults(array $config = []): array {
    $default = $this->defaultValues();
    // Keep only keys which are present in the default values.
    $values = array_intersect_key($config, $default);
    return $values + $default;
  }

  /**
   * {@inheritDoc}
   */
  public function appliesToTemplate(string $name): bool {
    $definition = $this->getPluginDefinition();
    return !empty($definition['target_template']) && $name === $definition['target_template'];
  }

  /**
   * {@inheritDoc}
   */
  public function preprocessVariables(array &$variables, array $configuration = []): void {}

  /**
   * {@inheritDoc}
   */
  public function globalSettingsForm(array &$form, FormStateInterface $formState, array $config = []): void {}

  /**
   * {@inheritDoc}
   */
  public function validateGlobalSettingsForm(array $form, FormStateInterface $formState) {}

  /**
   * {@inheritDoc}
   */
  public function submitGlobalSettingsForm(array $form, FormStateInterface $formState) {
    // @todo are we sure that json_encode keep the same order? Or should we
    //   sort the array first?
    $toString = fn($a) => json_encode($a);
    $fromString = fn($a) => json_decode($a, TRUE);
    $default = $this->defaultValues();
    $flattenDefault = array_map($toString, $default);

    // Keep only keys which are present in the default values.
    $values = array_intersect_key($formState->getValues(), $default);
    $flattenValues = array_map($toString, $values);
    // Keep only values that differs from defaults.
    $values = array_diff_assoc($flattenValues, $flattenDefault);
    $formState->setValues(array_map($fromString, $values));
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState): void {}

  /**
   * {@inheritDoc}
   */
  public function getMachineName(): string {
    return strtr($this->getPluginId(), [':' => '_']);
  }

  /**
   * Get the name of an input element from its path.
   *
   * Nested input names are based on the hierarchy of the element inside the
   * form. So when we use #states API, we may need to target a specific
   * element with its name.
   *
   * With the following arguments:
   * - select
   * - ['foo', 'bar']
   * - 'baz'
   *
   * The method will return the following string:
   * - select[name="foo[bar][baz]"]
   *
   * @param string $type
   *   The input type. It can also be ":input", which includes several elements
   *   like <button>, <textarea>, <select>...
   * @param string|array ...$keys
   *   Each parameter is either a string or an array of strings, which define
   *   all together the path of the input element.
   *
   * @return string
   */
  protected function getInputNameFromPath(string $type, string|array ...$keys): string {
    if ($keys) {
      $parents = array_reduce($keys, fn($parents, $key) => array_merge($parents, (array) $key), []);
      $root = array_shift($parents);
      return $type . '[name="' . $root . ($parents ? '[' . implode('][', $parents) . ']' : '') . '"]';
    }

    return '';
  }

  /**
   * Add the specified classes to the given attribute instance.
   */
  protected function addClasses(Attribute|array &$attributes, string $value) {
    foreach (preg_split('/\s+/', $value) as $class) {
      if ($attributes instanceof Attribute) {
        $attributes->addClass($class);
      }
      else {
        $attributes += ['class' => []];

        if (!in_array($class, $attributes['class'])) {
          $attributes['class'][] = $class;
        }
      }
    }
  }

}
