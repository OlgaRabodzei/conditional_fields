<?php

namespace Drupal\conditional_fields\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Url;

/**
 * Returns responses for conditional_fields module routes.
 */
class ConditionalFieldController extends ControllerBase {

  /**
   * Show entity types.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function entityTypeList() {
    $output = [
      '#theme' => 'admin_block_content',
      '#content' => [],
    ];

    /** @var ContentEntityType $entityType */
    foreach ($this->getEntityTypes() as $key => $entityType) {
      $output['#content'][] = [
        'url' => Url::fromRoute('conditional_fields.bundle_list', ['entity_type' => $key]),
        'title' => $entityType->getLabel(),
      ];
    }

    return $output;
  }

  /**
   * Show bundle list of current entity type.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function bundleList($entity_type) {
    $output = [];

    $bundles = \Drupal::getContainer()->get('entity_type.bundle.info')->getBundleInfo($entity_type);

    if ($bundles) {
      $output['#theme'] = 'admin_block_content';
      foreach ($bundles as $bundle_key => $bundle) {
        $output['#content'][] = [
          'url' => Url::fromRoute('conditional_fields.conditions_list', [
            'entity_type' => $entity_type,
            'bundle' => $bundle_key,
          ]),
          'title' => $bundle['label'],
        ];
      }
    }
    else {
      $output['#type'] = 'markup';
      $output['#markup'] = $this->t("Bundles not found");
    }

    return $output;
  }

  public function bundleListTitle($entity_type) {

    $result = \Drupal::entityTypeManager()->getStorage($entity_type);

    $output = [];
  }

  /**
   * Get list of available EntityTypes.
   */
  protected function getEntityTypes() {
    $entityTypes = [];

    foreach (\Drupal::entityTypeManager()->getDefinitions() as $key => $entityType) {
      if ($entityType instanceof ContentEntityType) {
        $entityTypes[$key] = $entityType;
      }
    }

    return $entityTypes;
  }

  /**
   * Provide arguments for ConditionalFieldFormTab.
   */
  public function provideArguments($node_type) {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\conditional_fields\Form\ConditionalFieldFormTab', 'node', $node_type);

    return $form;
  }

}
