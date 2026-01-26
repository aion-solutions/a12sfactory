<?php

namespace Drupal\a12sfactory;

use Drupal\Core\File\HtaccessWriter as CoreHtaccessWriter;

/**
 * Custom HtaccessWriter that skips .htaccess generation for nginx servers.
 */
class HtaccessWriter extends CoreHtaccessWriter {

  /**
   * {@inheritdoc}
   */
  public function ensure(): void {
    if ($this->supportHtAccessFile()) {
      parent::ensure();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultProtectedDirs(): array {
    return $this->supportHtAccessFile() ? parent::defaultProtectedDirs() : [];
  }

  /**
   * Checks if the server supports .htaccess files.
   *
   * @return bool
   */
  protected function supportHtAccessFile(): bool {
    $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
    // Perform .htaccess generation if running on Apache.
    return stripos($server_software, 'apache') !== FALSE;
  }

}
