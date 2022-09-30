<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\paragraphs\ParagraphInterface;

/**
 * @inheritDoc
 *
 * Alter the install settings form.
 *
 * @see hook_form_FORM_ID_alter()
 */
function a12sfactory_form_install_settings_form_alter(&$form, FormStateInterface $formState) {
  $database = Database::getConnectionInfo();

  if (!empty($database['default'])) {
    $handlers = [
      &$form['#submit'],
      &$form['actions']['save']['#submit'],
    ];

    foreach ($handlers as &$handler) {
      if (isset($handler)) {
        array_unshift($handler, 'a12sfactory_form_install_settings_form_submit');

        if ($key = array_search('::submitForm', $handler)) {
          unset($handler[$key]);
          $form['#has_submit_callback'] = TRUE;
        }
      }
    }
  }

  $form['settings'][$database['default']['driver']]['password']['#description'] = t('Leave empty to use the password that is defined in the <em>settings.php</em> file.');
}

/**
 * Form submit callback; force the database password if applicable.
 *
 * @throws \Exception
 */
function a12sfactory_form_install_settings_form_submit(array &$form, FormStateInterface $form_state) {
  // Take care if the database connexion
  $database = Database::getConnectionInfo();

  if (!empty($database['default'])) {
    $user_values = $form_state->get('database');
    $diff = array_diff($user_values, $database['default']);

    if (!isset($diff['database']) && isset($diff['password']) && $diff['password'] === '') {
      global $installState;

      // Perform same tasks as in @see SiteSettingsForm::submitForm(), without
      // writing the database definition to the settings as it already exists.
      $settings = [];
      $settings['settings']['hash_salt'] = (object) [
        'value'    => Crypt::randomBytesBase64(55),
        'required' => TRUE,
      ];
      // Remember the profile which was used.
      $settings['settings']['install_profile'] = (object) [
        'value' => $installState['parameters']['profile'],
        'required' => TRUE,
      ];

      drupal_rewrite_settings($settings);
      $installState['settings_verified'] = TRUE;
      $installState['config_verified'] = TRUE;
      $installState['database_verified'] = TRUE;
      $installState['completed_task'] = install_verify_completed_task();
      return;
    }
  }

  // Fallback to default behavior.
  if (!empty($form['#has_submit_callback'])) {
    call_user_func_array($form_state->prepareCallback('::submitForm'), [&$form, &$form_state]);
  }
}

/**
 * @inheritDoc
 *
 * Alter the site configuration form.
 *
 * @see hook_form_FORM_ID_alter()
 */
function a12sfactory_form_install_configure_form_alter(&$form, FormStateInterface $formState) {
  $form['site_information']['site_name']['#attributes']['placeholder'] = t('Specify the site name');

  // Default user 1 username should be 'Webmaster'.
  $form['admin_account']['account']['name']['#default_value'] = 'Webmaster';
  $form['admin_account']['account']['name']['#attributes']['disabled'] = TRUE;
  unset($form['admin_account']['account']['name']['#description']);

  $form['admin_account']['account']['mail']['#default_value'] = 'dev@a12s.io';
  $form['admin_account']['account']['mail']['#attributes']['disabled'] = TRUE;

  $form['regional_settings']['date_default_timezone']['#default_value'] = 'Europe/Paris';

  $form['update_notifications']['enable_update_status_emails']['#default_value'] = 0;
  $form['update_notifications']['enable_update_status_module']['#default_value'] = 0;
}

/**
 * @inheritDoc
 * @see hook_theme()
 */
function a12sfactory_theme($existing, $type, $theme, $path) {
  $path = \Drupal::service('extension.path.resolver')->getPath('module', 'a12sfactory') . '/templates';
  $baseParagraph = [
    'base hook' => 'paragraph',
    'path' => $path . '/paragraph',
  ];
  $baseField = [
    'base hook' => 'field',
    'path' => $path . '/field',
  ];

  return [
    'paragraph__default' => $baseParagraph,
    'paragraph__grid_row' => $baseParagraph,
    'paragraph__image' => $baseParagraph,
    'paragraph__cards' => $baseParagraph,
    'paragraph__card' => $baseParagraph,
    'paragraph__card_body' => $baseParagraph,
    'field__entity_reference_revisions' => $baseField,
    'field__field_card_list_items' => $baseField,
    'field__field_card_links' => $baseField,
  ];
}

/**
 * Implements hook_preprocess_html().
 */
function a12sfactory_preprocess_html(&$variables) {
  if (!empty($variables['page_top']['toolbar']) && (bool) \Drupal::request()->query->get('hide_toolbar') === TRUE) {
    $variables['page_top']['toolbar']['#access'] = FALSE;
  }
}

/**
 * @inheritDoc
 * @see hook_preprocess_HOOK()
 */
function a12sfactory_preprocess_paragraph(&$variables) {
  /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
  $paragraph = &$variables['paragraph'];

  /** @var \Drupal\a12sfactory\Plugin\paragraphs\Behavior\A12sGridBehavior $gridBehavior */
  $gridBehavior = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_grid');
  $gridBehaviorConfig = $gridBehavior->getConfiguration();

  $isRoot = $gridBehavior->paragraphIsRoot($paragraph);
  $isColumn = $gridBehavior->paragraphIsColumn($paragraph);
  $isRow = a12sfactory_paragraph_is_row($paragraph);

  // Add an ID so editors may use anchors inside their links, or attach
  // background images...
  if (empty($variables['attributes']['id'])) {
    $variables['attributes']['id'] = 'p-' . $paragraph->uuid();
  }

  // This is used to handle rows width, as those cannot use their column to
  // specify the offset. It is only implemented in the following template:
  // - paragraph--grid-row.html.twig
  if (!isset($variables['wrap_with_grid'])) {
    $variables['wrap_with_grid'] = FALSE;

    if (isset($variables['elements']['#wrap_with_grid'])) {
      $variables['wrap_with_grid'] = $variables['elements']['#wrap_with_grid'];
    }
  }

  if (!isset($variables['add_container'])) {
    $variables['add_container'] = $isRoot/* || !$isColumn*/;

    if (isset($variables['elements']['#add_container'])) {
      $variables['add_container'] = $variables['elements']['#add_container'];
    }
  }

  if (!isset($variables['add_grid'])) {
    // Add a grid for the paragraph types that also define a "row_type". So this
    // excludes for example the column "single".
    $variables['add_grid'] = $isRoot || ($isRow && !empty($gridBehaviorConfig['row_type']));

    if (isset($variables['elements']['#add_grid'])) {
      $variables['wrap_with_grid'] = $variables['elements']['#add_grid'];
    }
  }

  if (!isset($variables['container_attributes'])) {
    // @todo use container_attributes_array and convert in a hook_process function.
    $variables['container_attributes'] = new Attribute();
  }

  if (!isset($variables['row_attributes'])) {
    // @todo use row_attributes_array and convert in a hook_process function.
    $variables['row_attributes'] = new Attribute();
  }

  if (!isset($variables['column_attributes'])) {
    // @todo use column_attributes_array and convert in a hook_process function.
    $variables['column_attributes'] = new Attribute();
    $variables['column_attributes']->addClass('col-12');
  }

  if ($isColumn) {
    $variables['attributes']['class'][] = 'paragraph--grid-column';
  }

  if (!empty($gridBehaviorConfig['enabled'])) {
    $width = $paragraph->getBehaviorSetting($gridBehavior->getPluginId(), ['column', 'width']);

    if (!empty($width)) {
      $variables['add_grid'] = TRUE;
    }

    if ($width === 'edge2edge') {
      $variables['add_container'] = FALSE;
      $variables['row_attributes']->addClass('no-gutters');
    }
  }

  if ($paragraph->bundle() === 'image' && $paragraph->get('image_field')->isEmpty()) {
    $variables['attributes']['class'][] = 'empty-image';
  }

  //if ($paragraph->getBehaviorSetting($gridBehavior->getPluginId(), ['column', 'cover'])) {
  if (!empty($variables['elements']['#context']['behavior_cover'])) {
    $variables['attributes']['class'][] = 'behavior-cover';
  }

  if ($isRow) {
    // Handle "cover" behavior for image and video paragraphs.
    foreach ($paragraph->getFieldDefinitions() as $name => $fieldDefinition) {
      if (strpos($name, 'column_') === 0 && $fieldDefinition->getType() === 'entity_reference_revisions') {
        // The "cover" behavior needs some extra process when first or last.
        $totalItems = $paragraph->get($name)->count();
        $layout = $gridBehavior->getRowLayout($paragraph, $totalItems);
        $columnBreakpoint = $paragraph->getBehaviorSetting($gridBehavior->getPluginId(), ['row', 'column_breakpoint'], 'lg');

        if ($totalItems > 1) {
          foreach ([0, ($totalItems - 1)] as $delta) {
            $position = $delta === 0 ? 'first' : 'last';

            if ($paragraph->get($name)->offsetExists($delta)) {
              $child = $paragraph->get($name)->get($delta);

              if (isset($child->entity) && $child->entity instanceof ParagraphInterface) {
                /** @var \Drupal\paragraphs\ParagraphInterface $childParagraph */
                $childParagraph = $child->entity;

                // @todo Factorize the bundle list and make it configurable
                //   through UI.
                if (in_array($childParagraph->bundle(), ['image', 'video'])) {
                  $cover = $childParagraph->getBehaviorSetting($gridBehavior->getPluginId(), ['column', 'cover']);

                  if ($cover && isset($variables['content'][$name][$delta])) {
                    $order = $childParagraph->getBehaviorSetting($gridBehavior->getPluginId(), ['column', 'order']);

                    // @todo Parse all the order settings for all children in
                    //   order to determinate the correct position.
                    if ($order) {
                      $position = 'last';
                    }

                    $variables['cover'][$position]['content'] = $variables['content'][$name][$delta];
                    // It seems not necessary to provide cache keys there, as
                    // the Paragraph entity defines the "render_cache" property
                    // as FALSE.
                    //$variables['cover'][$position]['content']['#cache']['keys'][] = 'behavior-cover';
                    $variables['cover'][$position]['content']['#context']['behavior_cover'] = TRUE;

                    // Find a better way to handle the classes, as this will
                    // fail for some configurations, as soon as the column width
                    // is not clearly defined (for example 5 equal columns).
                    $variables['cover'][$position]['classes'] = preg_split('/\s+/', $layout[$delta]);

                    if ($columnBreakpoint === '_all') {
                      $variables['attributes']['class'][] = 'behavior-cover';
                    }
                    elseif ($columnBreakpoint) {
                      $variables['attributes']['class'][] = 'behavior-cover-' . $columnBreakpoint;
                    }

                    $variables['cover'][$position]['classes'][] = 'd-none';
                    $variables['cover'][$position]['classes'][] = "d-$columnBreakpoint-block";

                    if ($position === 'last') {
                      $variables['cover'][$position]['classes'][] = 'ml-auto';
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

/**
 * @inheritDoc
 * @see hook_preprocess_HOOK()
 */
function a12sfactory_preprocess_field__entity_reference_revisions(&$variables) {
  if (!empty($variables['element']['#object'])) {
    // Do not add useless class on the root element.
    $variables['add_column'] = FALSE;

    if ($variables['element']['#object'] instanceof ParagraphInterface) {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $variables['element']['#object'];

      /** @var \Drupal\a12sfactory\Plugin\paragraphs\Behavior\A12sGridBehavior $gridBehavior */
      $gridBehavior = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_grid');
      $isRoot = $gridBehavior->paragraphIsRoot($paragraph);

      // Do not wrap the content of a card group and remove the label.
      if ($paragraph->bundle() === 'cards') {
        $variables['label_hidden'] = TRUE;
      }
      // For "columns_single" paragraphs, only add a column when they are root.
      elseif ($paragraph->bundle() !== 'columns_single' || $isRoot) {
        $variables['add_column'] = TRUE;
      }

      if (a12sfactory_paragraph_is_row($paragraph)) {
        $totalItems = count($variables['items']);
        $layout = $gridBehavior->getRowLayout($paragraph, $totalItems);

        $firstItem = NULL;
        $lastItem = NULL;
        $extraOffset = NULL;

        foreach ($variables['items'] as $index => &$item) {
          if (isset($item['content']['#paragraph'])) {
            /** @var \Drupal\paragraphs\ParagraphInterface $childParagraph */
            $childParagraph = $item['content']['#paragraph'];
            $columnSettings = $childParagraph->getBehaviorSetting($gridBehavior->getPluginId(), 'column', []);
            $order = $gridBehavior->parseColumnOrder($childParagraph);

            $item['content']['#first_column'] = FALSE;
            $item['content']['#last_column'] = FALSE;
            $item['content']['#item_index'] = $index;
            $item['content']['#total_items'] = $totalItems;

            if (!isset($firstItem)) {
              $firstItem = [
                'order' => $gridBehavior->parseColumnOrder($childParagraph),
                'item' => &$item,
              ];
            }
            elseif ($firstItem['order'] !== 'first') {
              $order = $gridBehavior->parseColumnOrder($childParagraph);
              $isFirst = $order === 'first';
              $currentNoPriority = !is_int($firstItem['order']) && is_int($order);
              $currentGreater = is_int($firstItem['order']) && is_int($order) && $order < $firstItem['order'];

              if ($isFirst || $currentNoPriority || $currentGreater) {
                $firstItem = [
                  'order' => $order,
                  'item' => &$item,
                ];
              }
            }

            if (!isset($lastItem)) {
              $lastItem = [
                'order' => $gridBehavior->parseColumnOrder($childParagraph),
                'item' => &$item,
              ];
            }
            else {
              $is_last = $order === 'last';
              $currentNoPriority = !is_int($lastItem['order']) || ($lastItem['order'] !== 'last' && $order !== 'first');
              $firstLower = is_int($firstItem['order']) && is_int($order) && $order > $firstItem['order'];

              if ($is_last || $currentNoPriority || $firstLower) {
                $lastItem = [
                  'order' => $order,
                  'item' => &$item,
                ];
              }
            }

            if (!empty($layout[$index])) {
              $item['attributes']->addClass($layout[$index]);
            }

            if (!empty($columnSettings['align_self'])) {
              $item['attributes']->addClass($columnSettings['align_self']);
            }

            if (!empty($columnSettings['order'])) {
              $item['attributes']->addClass($columnSettings['order']);
            }

            if (!empty($columnSettings['class'])) {
              $classes = preg_split('/\s+/', $columnSettings['class']);
              $item['attributes']->addClass($classes);
            }

            if ($extraOffset) {
              $item['attributes']->addClass($extraOffset);
              $extraOffset = NULL;
            }

            // Handle "cover" behavior.
            if ($childParagraph->getBehaviorSetting($gridBehavior->getPluginId(), ['column', 'cover'])) {
              $columnBreakpoint = $paragraph->getBehaviorSetting($gridBehavior->getPluginId(), ['row', 'column_breakpoint'], 'lg');

              if (!empty($columnBreakpoint)) {
                if ($columnBreakpoint === '_all') {
                  $columnBreakpoint = '';
                }
                else {
                  $columnBreakpoint = '-' . $columnBreakpoint;
                }

                $colMatch = [];

                if ($index === 0 || $index === ($totalItems - 1)) {
                  $item['attributes']->addClass("d$columnBreakpoint-none");
                }

                // @todo manage correctly the order...
                if (empty($order) && $index === 0 && preg_match('/\bcol' . preg_quote($columnBreakpoint) . '-(?<length>\d+)\b/', (string) $item['attributes']->getClass(), $colMatch)) {
                  $extraOffset = "offset$columnBreakpoint-{$colMatch['length']}";
                }
              }
            }
          }
        }

        if (isset($firstItem)) {
          $firstItem['item']['content']['#first_column'] = TRUE;
        }

        if (isset($lastItem)) {
          $lastItem['item']['content']['#last_column'] = TRUE;
        }
      }
    }
  }
}

/**
 * Whether the given paragraph is a row.
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *
 * @return bool
 */
function a12sfactory_paragraph_is_row(ParagraphInterface $paragraph) {
  $config = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_grid')->getConfiguration();
  return (!empty($config['enabled']) && !empty($config['is_row']));
}

/**
 * @inheritDoc
 * @see hook_slick_skins_info()
 */
function a12sfactory_slick_skins_info() {
  return '\\Drupal\\a12sfactory\\Slick\\A12sfactorySlickSkin';
}

/**
 * {@inheritDoc}
 *
 * Forked from the issue below. Integrated in the profile as it makes the
 * upgrade to the final way of handling translations that will be chosen by the
 * maintainers of Paragraph and ERR modules.
 *
 * This is a simplified version, as we always want to synchronise translations
 * for all translatable paragraphs. Having a single and defined workflow ensure
 * compatibility with upgrades.
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph entity.
 *
 * @see https://www.drupal.org/project/paragraphs/issues/2887353
 *
 * @ingroup "Paragraph symetric translations"
 *
 * @see hook_ENTITY_TYPE_insert()
 */
function a12sfactory_paragraph_insert(ParagraphInterface $paragraph) {
  \Drupal::service('a12sfactory.paragraphs_translation_synchronization')->deferSync($paragraph);

  try {
    /** @var \Drupal\a12sfactory\Plugin\paragraphs\Behavior\A12sDisplayBehavior $plugin */
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_display');
    $plugin->addFileUsage($paragraph);
  }
  catch (PluginException $e) {}
}

/**
 * {@inheritDoc}
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph entity.
 *
 * @see hook_ENTITY_TYPE_update()
 */
function a12sfactory_paragraph_update(ParagraphInterface $paragraph) {
  try {
    /** @var \Drupal\a12sfactory\Plugin\paragraphs\Behavior\A12sDisplayBehavior $plugin */
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_display');
    $plugin->mergeFileUsage($paragraph);
  }
  catch (PluginException $e) {}
}

/**
 * {@inheritDoc}
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph entity.
 *
 * @see hook_ENTITY_TYPE_delete()
 */
function a12sfactory_paragraph_delete(ParagraphInterface $paragraph) {
  try {
    /** @var \Drupal\a12sfactory\Plugin\paragraphs\Behavior\A12sDisplayBehavior $plugin */
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_display');
    $plugin->deleteFileUsage($paragraph, 0);
  }
  catch (PluginException $e) {}
}

/**
 * {@inheritDoc}
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph entity.
 *
 * @see hook_ENTITY_TYPE_revision_delete()
 */
function a12sfactory_paragraph_revision_delete(ParagraphInterface $paragraph) {
  try {
    /** @var \Drupal\a12sfactory\Plugin\paragraphs\Behavior\A12sDisplayBehavior $plugin */
    $plugin = $paragraph->getParagraphType()->getBehaviorPlugin('a12sfactory_paragraph_display');
    $plugin->deleteFileUsage($paragraph);
  }
  catch (PluginException $e) {}
}

/**
 * @inheritDoc
 *
 * Allow simple text field to use a textarea to enter multilines text.
 *
 * @see hook_field_widget_info_alter()
 */
function a12sfactory_field_widget_info_alter(array &$info) {
  $info['text_textarea']['field_types'][] = 'text';
  $info['string_textarea']['field_types'][] = 'string';
}
