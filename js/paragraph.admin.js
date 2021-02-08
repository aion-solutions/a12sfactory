/**
 * @file
 * Behaviors Varbase hero slider media general scripts.
 */

(function ($, _, Drupal, drupalSettings) {
  "use strict";
  Drupal.behaviors.varbaseBootstrapParagraphsAdmin = {
    attach: function attach(context) {

      $(".field--name-background.field--widget-options-buttons input:radio").each(function() {
        $(this).next('label').addClass($(this).val());
      });
    }
  };

})(window.jQuery, window._, window.Drupal, window.drupalSettings);
