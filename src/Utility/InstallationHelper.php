<?php

namespace Drupal\a12sfactory\Utility;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Helper class for various installation tasks.
 *
 * This is not a service, as this class is only intended to be used for
 * installation tasks.
 */
class InstallationHelper {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new InstallationHelper.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  protected function __construct(
    protected string $root,
    protected MessengerInterface $messenger,
    protected ModuleInstallerInterface $moduleInstaller,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StorageInterface $configStorage,
  ) {}

  /**
   * Get the helper instance.
   *
   * @noinspection PhpParamsInspection
   */
  public static function instance(): static {
    static $instance;

    if (!$instance) {
      $container = \Drupal::getContainer();
      $instance = new static(
        $container->getParameter('app.root'),
        $container->get('messenger'),
        $container->get('module_installer'),
        $container->get('module_handler'),
        $container->get('file_system'),
        $container->get('entity_type.manager'),
        $container->get('config.storage'),
      );
    }

    return $instance;
  }

  /**
   * Get the batch definition for multilingual support.
   *
   * @param array $languages
   *   An array of language codes.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function installLanguages(array $languages): void {
    foreach ($languages as $language) {
      ConfigurableLanguage::createFromLangcode($language)->save();
    }
  }

  /**
   * Batch installation callback; enables a list of modules using batch.
   *
   * @param array $modules
   *   The list of modules to install.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function installModules(array $modules): void {
    $this->moduleInstaller->install($modules);
  }

  /**
   * Add Permissions for user roles from scanned directory.
   *
   * @param string $feature
   *   The feature name.
   */
  public function installPermissions(string $feature): void {
    try {
      $roleStorage = $this->entityTypeManager->getStorage('user_role');
      $path = $this->moduleHandler->getModule('a12sfactory')->getPath();
      $file = $path . '/config/features/' . $feature . '/permissions.yml';

      try {
        if (file_exists($file) && $data = Yaml::decode(file_get_contents($file))) {
          foreach (array_filter((array) $data) as $role => $permissions) {
            if ($roleEntity = $roleStorage->load($role)) {
              foreach ($permissions as $permission) {
                $roleEntity->grantPermission($permission);
              }

              $roleEntity->save();
            }
          }
        }
      }
      catch (InvalidDataTypeException $e) {
        $this->messenger->addWarning($this->t('The file %filename does not contain valid data and has been ignored.', [
          '%filename' => $file,
        ]));
      }
    }
    catch (NotRegularDirectoryException $e) {
      $this->messenger->addWarning($this->t('The feature %feature does not define permission files.', [
        '%feature' => $feature,
      ]));
    }
    catch (\Exception $e) {
      $this->messenger->addWarning($this->t('An error occurred while installing permissions for the feature %feature.', [
        '%feature' => $feature,
      ]));
    }
  }

  /**
   * Retrieves extra features from Yaml files.
   *
   * This method loads extra features from Yaml files located in a specified folder.
   *
   * @return array An array containing the extra features.
   */
  public function getFeatures(): array {
    // Load extra features from Yaml
    $extraFeaturesFolder = $this->getFeaturesFolder();

    if (file_exists($extraFeaturesFolder) && is_dir($extraFeaturesFolder)) {
      $directories = glob($extraFeaturesFolder . '/*', GLOB_ONLYDIR);
      foreach ($directories as $dir) {
        $infoFile = $dir . '/info.yml';
        if (file_exists($infoFile)) {
          $extraFeatures[basename($dir)] = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($infoFile));
        }
      }
    }

    return $extraFeatures ?? [];
  }

  /**
   * Retrieves the folder path for extra features.
   *
   * This method returns the folder path where the extra features are stored.
   *
   * @return string The folder path for extra features.
   */
  public function getFeaturesFolder(): string {
    return $this->root . '/' . \Drupal::service('extension.list.profile')->getPath('a12sfactory') . '/config/features';
  }

  /**
   * @param string $configName
   * @param array $data
   * @return void
   */
  public function writeConfig(string $configName, array $data) {
    $this->configStorage->write($configName, $data);
  }

}
