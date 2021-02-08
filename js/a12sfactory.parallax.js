(function ($, Drupal) {

  "use strict";

  /**
   * Drupal behaviors for Paragraph Parallax.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.a12sfactoryParagraphParallax = {

    /**
     * Attach Drupal behaviors.
     *
     * @param context Element|jQuery The current execution context
     */
    attach: function attach(context) {
      $('.a12sfactory-paragraph-parallax', context).once('a12sfactory-paragraph-parallax').paroller();
    }

  };

}(jQuery, window.Drupal));
