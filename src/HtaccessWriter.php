
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
    // Check if the server supports .htaccess files.
    // Apache uses 'apache' or 'apache2handler' as SAPI
    $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';

    // Perform .htaccess generation if running on Apache.
    if (stripos($server_software, 'apache') !== FALSE) {
      parent::ensure();
    }
  }

}
