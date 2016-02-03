<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\WorkspaceTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * Test the workspace entity.
 *
 * @group multiversion
 */
class WorkspaceTest extends MultiversionWebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['multiversion', 'key_value'];

  public function testOperations() {
    $default = Workspace::load(1);
    $this->assertTrue(!empty($default), 'Default workspace was created when installing Multiversion module.');
    $machine_name = $this->randomMachineName();
    $entity = Workspace::create(['machine_name' => $machine_name, 'label' => $machine_name]);

    $this->assertTrue($entity instanceof WorkspaceInterface, 'Workspace entity was created.');

    $entity->save();
    $this->assertEqual($machine_name, $entity->get('machine_name')->value, 'Workspace entity was saved.');

    $entity = Workspace::load($entity->id());
    $this->assertEqual($machine_name, $entity->get('machine_name')->value, 'Workspace entity was loaded by ID.');

    $entity = $this->entityManager->loadEntityByUuid('workspace', $entity->uuid());
    $this->assertEqual($machine_name, $entity->get('machine_name')->value, 'Workspace entity was loaded by UUID.');
    $this->assertEqual($machine_name, $entity->label(), 'Label method returns the workspace name.');

    $created = $entity->getStartTime();
    $this->assertNotNull($created, "The value for 'created' field is not null.");

    $new_created_time = microtime(TRUE) * 1000000;
    $entity->setCreatedTime((int) $new_created_time);
    $this->assertEqual($new_created_time, $entity->getStartTime(), "Correct value for 'created' field.");
  }

  public function testSpecialCharacters() {
    //  Note that only lowercase characters (a-z), digits (0-9),
    // or any of the characters _, $, (, ), +, -, and / are allowed.
    $workspace1 = Workspace::create(['label' => 'Workspace 1', 'machine_name' => 'a0_$()+-/']);
    $violations1 = $workspace1->validate();
    $this->assertEqual($violations1->count(), 0, 'No violations');

    $workspace2 = Workspace::create(['label' => 'Workspace 2', 'machine_name' => 'A!"£%^&*{}#~@?']);
    $violations2 = $workspace2->validate();
    $this->assertEqual($violations2->count(), 1, 'One violation');

    $this->webUser = $this->drupalCreateUser([
      'administer workspaces',
    ]);
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/structure/workspaces/add');
    $workspace3 = [
      'label' => 'Workspace 1',
      'machine_name' => 'a0_$()+-/',
    ];
    $this->drupalPostForm('admin/structure/workspaces/add', $workspace3, t('Save'));

    $this->drupalGet('admin/structure/workspaces');
    $this->assertText($workspace3['label'], 'Workspace found in list of workspaces');

    $workspace4 = [
      'label' => 'Workspace 2',
      'machine_name' => 'A!"£%^&*{}#~@?',
    ];
    $this->drupalPostForm('admin/structure/workspaces/add', $workspace4, t('Save'));

    $this->drupalGet('admin/structure/workspaces');
    $this->assertNoText($workspace4['label'], 'Workspace not found in list of workspaces');
  }
}
