<?php

namespace Drupal\Tests\conditional_fields\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

use Drupal\conditional_fields\Controller\ConditionalFieldController;

/**
 * ConditionalFieldController units tests.
 *
 * In unit testing, there should be as few dependencies as possible.
 * We want the smallest number of moving parts to be interacting in
 * our test, or we won't be sure where the errors are, or whether our
 * tests passed by accident.
 *
 * @ingroup conditional_fields
 *
 * @group conditional_fields
 */
class ConditionalFieldControllerTest extends UnitTestCase {

  protected $controller;

 /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity_types = [];
    // `content_a` should appear in test results.
    $entity_types['content_a'] = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityType')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_types['content_a']->expects($this->any())
      ->method('getLabel')
      ->will($this->returnValue("contentA"));

    // `content_b` shouldn't appear in test results.
    $entity_types['content_b'] = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_types['content_b']->expects($this->any())
      ->method('getLabel')
      ->will($this->returnValue("contentB"));

    // Setup Drupal Container.
    $entity_type_manager = $this->getMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_type_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($entity_types));
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    // ConditionalFieldController::create();
    $this->controller = new ConditionalFieldController();
  }

  /**
   * Very simple test of ConditionalFieldController::entityTypeList().
   *
   * This is a very simple unit test of a single method.
   * It checks that only instances of `ContentEntityType` are available.
   */
  public function testEntityTypeList() {
    $available_entity_types = $this->controller->entityTypeList()['#content'];
    $this->assertEquals(1, count($available_entity_types));
    $this->assertEquals($available_entity_types[0]['title'], 'contentA');
  }
}
