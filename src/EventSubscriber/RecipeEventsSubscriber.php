<?php

declare(strict_types=1);

namespace Drupal\a12sfactory\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeAppliedEvent;
use Drupal\Core\State\StateInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

class RecipeEventsSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the RecipeEventSubscriber
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   Module installer service
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler service
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(
    protected StateInterface $state,
    protected ConfigFactoryInterface $configFactory,
    protected ModuleInstallerInterface $moduleInstaller,
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleExtensionList $moduleExtensionList,
    protected ThemeHandlerInterface $themeHandler,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RecipeAppliedEvent::class => ['onRecipeApplied'],
    ];
  }

  /**
   * Event subscriber callback.
   *
   * @param \Drupal\Core\Recipe\RecipeAppliedEvent $event
   *   Event containing information about the applied recipe.
   */
  public function onRecipeApplied(RecipeAppliedEvent $event): void {
    $i18nInstalling = FALSE;

    if (str_ends_with($event->recipe->path, 'a12sfactory/recipes/i18n')) {
      $this->state->set('a12sfactory.i18n.installed', TRUE);
      $i18nInstalling = TRUE;
      $inputs = $event->recipe->input->getValues() ?? [];

      // Handle "recipe_i18n_install_optional_configuration" setting.
      $installOptionalConfiguration = filter_var($inputs['recipe_i18n_install_optional_configuration'], FILTER_VALIDATE_BOOLEAN);
      $a12sfactorySettings = $this->configFactory->getEditable('a12sfactory.settings');
      $a12sfactorySettings->set('recipe_i18n_install_optional_configuration', $installOptionalConfiguration);
      $a12sfactorySettings->save();

      $languages = preg_split('/\s*(,\s*)+/',  trim($inputs['languages'], " \n\r\t\v\0,")) ?: [];
      $this->installLanguages($languages);
    }

    if ($this->state->get('a12sfactory.i18n.installed', FALSE)) {
      $enabled = $this->configFactory->get('a12sfactory.settings')->get('recipe_i18n_install_optional_configuration') ?? FALSE;

      if ($enabled) {
        $this->alterConfiguration($event->recipe, $i18nInstalling);
      }
    }
  }

  /**
   * Installs the specified languages
   *
   * @param array $languages
   *   Array of languages codes to install
   */
  protected function installLanguages(array $languages): void {
    foreach ($languages as $language) {
      if (!$this->languageManager->getLanguage($language)) {
        try {
          $lang = ConfigurableLanguage::createFromLangcode($language);
          $lang->save();
        }
        catch(\Exception $e) {
          // Pass through
        }
      }
    }
  }

  /**
   * Handles the installation of optional configuration for both the current
   * recipe and the i18n recipe.
   *
   * @param \Drupal\Core\Recipe\Recipe $recipe
   *   Recipe object containing the current applied recipe.
   * @param bool $i18nInstalling
   *   TRUE if the i18n recipe is currently being installed, FALSE otherwise.
   */
  protected function alterConfiguration(Recipe $recipe, bool $i18nInstalling = FALSE): void {
    $this->installOptionalConfiguration($recipe->path . '/config/optional');

    if (!$i18nInstalling) {
      $profilePath = $this->moduleExtensionList->getPath('a12sfactory');
      $i18nPath = $profilePath . '/recipes/i18n/config/optional';
      $this->installOptionalConfiguration($i18nPath);
    }
  }

  /**
   * Install an optional configuration.
   *
   * @param string $path
   *   The path to the directory containing optional configuration files.
   */
  protected function installOptionalConfiguration(string $path): void {
    if (!is_dir($path)) {
      return;
    }

    foreach (glob($path . '/*.yml') as $file) {
      try {
        $data = Yaml::parseFile($file);
        $dependenciesMeet = TRUE;

        foreach ($data['dependencies'] ?? [] as $type => $dependencies) {
          switch ($type) {
            case 'module':
              foreach ($dependencies as $module) {
                if (!$this->moduleHandler->moduleExists($module)) {
                  $dependenciesMeet = FALSE;
                  break 3;
                }
              }
              break;

            case 'config':
              $configs = $this->configFactory->loadMultiple($dependencies);

              if (count($dependencies) !== count($configs)) {
                $dependenciesMeet = FALSE;
                break 2;
              }
              break;

            case 'theme':
              foreach ($dependencies as $theme) {
                if (!$this->themeHandler->themeExists($theme)) {
                  $dependenciesMeet = FALSE;
                  break 3;
                }
              }
              break;
          }
        }

        if ($dependenciesMeet) {
          $this->importOptionalConfiguration($file);
        }
      }
      catch (\Throwable $e) {
        // Pass through
      }
    }
  }

  /**
   * Imports optional configuration from a file into the active configuration.
   *
   * @param string $file
   *   The path to the YAML configuration file to import.
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   */
  protected function importOptionalConfiguration(string $file): void {
    $configName = basename($file, '.yml');
    $configStorage = new FileStorage(dirname($file));
    $data = $configStorage->read($configName);

    if ($data) {
      $this->configFactory->getEditable($configName)->setData($data)->save();
    }
  }

}
