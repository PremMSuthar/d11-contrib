<?php

namespace Drupal\site_analyzer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for analyzing content structure and usage.
 */
class ContentAnalyzer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ContentAnalyzer object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
  }

  /**
   * Analyzes content structure and usage.
   */
  public function analyze() {
    return [
      'content_types' => $this->analyzeContentTypes(),
      'field_types' => $this->analyzeFieldTypes(),
      'content_volume' => $this->analyzeContentVolume(),
      'media_usage' => $this->analyzeMediaUsage(),
      'taxonomy_structure' => $this->analyzeTaxonomyStructure(),
      'menu_structure' => $this->analyzeMenuStructure(),
      'block_usage' => $this->analyzeBlockUsage(),
      'user_roles' => $this->analyzeUserRoles(),
      'deprecated_fields' => $this->findDeprecatedFields(),
      'field_usage_stats' => $this->getFieldUsageStats(),
      // Enhanced site_audit content functionality
      'all_entity_types' => $this->analyzeAllEntityTypes(),
      'content_entity_types_detailed' => $this->analyzeContentEntityTypesDetailed(),
      'unused_content_types' => $this->findUnusedContentTypes(),
      'unused_vocabularies' => $this->findUnusedVocabularies(),
      'entity_bundles_analysis' => $this->analyzeEntityBundles(),
      'site_audit_content_recommendations' => $this->generateSiteAuditContentRecommendations(),
    ];
  }

  /**
   * Analyzes content types.
   */
  protected function analyzeContentTypes() {
    $content_types = [];
    
    try {
      $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      
      foreach ($node_types as $type_id => $type) {
        $fields = $this->entityFieldManager->getFieldDefinitions('node', $type_id);
        $field_count = count($fields);
        $content_count = $this->getContentCount('node', $type_id);
        
        $content_types[$type_id] = [
          'id' => $type_id,
          'label' => $type->label(),
          'description' => $type->getDescription(),
          'field_count' => $field_count,
          'content_count' => $content_count,
          'fields' => $this->analyzeContentTypeFields($fields),
          'display_modes' => $this->getDisplayModes('node', $type_id),
          'form_modes' => $this->getFormModes('node', $type_id),
          'workflow_states' => $this->getWorkflowStates($type_id),
        ];
      }
    }
    catch (\Exception $e) {
      // Log error but continue
    }
    
    return $content_types;
  }

  /**
   * Analyzes fields for a content type.
   */
  protected function analyzeContentTypeFields($fields) {
    $field_analysis = [];
    
    foreach ($fields as $field_name => $field) {
      // Skip base fields for brevity
      if ($field->getFieldStorageDefinition()->isBaseField()) {
        continue;
      }
      
      $field_analysis[$field_name] = [
        'label' => $field->getLabel(),
        'type' => $field->getType(),
        'required' => $field->isRequired(),
        'cardinality' => $field->getFieldStorageDefinition()->getCardinality(),
        'settings' => $field->getSettings(),
        'default_value' => $field->getDefaultValueLiteral(),
        'description' => $field->getDescription(),
      ];
    }
    
    return $field_analysis;
  }

  /**
   * Gets content count for an entity type and bundle.
   */
  protected function getContentCount($entity_type, $bundle = null) {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $query = $storage->getQuery()->accessCheck(FALSE)->count();
      
      if ($bundle) {
        $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
        if ($bundle_key) {
          $query->condition($bundle_key, $bundle);
        }
      }
      
      return (int) $query->execute();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Analyzes field types across the site.
   */
  protected function analyzeFieldTypes() {
    $field_types = [];
    $entity_types = ['node', 'user', 'taxonomy_term', 'media', 'paragraph'];
    
    foreach ($entity_types as $entity_type) {
      try {
        $bundles = $this->entityTypeManager->getStorage($entity_type . '_type')->loadMultiple();
        
        foreach ($bundles as $bundle_id => $bundle) {
          $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle_id);
          
          foreach ($fields as $field_name => $field) {
            if (!$field->getFieldStorageDefinition()->isBaseField()) {
              $field_type = $field->getType();
              
              if (!isset($field_types[$field_type])) {
                $field_types[$field_type] = [
                  'type' => $field_type,
                  'count' => 0,
                  'usage' => [],
                  'is_deprecated' => $this->isFieldTypeDeprecated($field_type),
                ];
              }
              
              $field_types[$field_type]['count']++;
              $field_types[$field_type]['usage'][] = [
                'entity_type' => $entity_type,
                'bundle' => $bundle_id,
                'field_name' => $field_name,
              ];
            }
          }
        }
      }
      catch (\Exception $e) {
        // Continue with other entity types
      }
    }
    
    return $field_types;
  }

  /**
   * Checks if a field type is deprecated.
   */
  protected function isFieldTypeDeprecated($field_type) {
    $deprecated_types = [
      'field_collection',
      'addressfield',
      'field_permissions',
    ];
    
    return in_array($field_type, $deprecated_types);
  }

  /**
   * Analyzes content volume.
   */
  protected function analyzeContentVolume() {
    $volume = [];
    
    $entity_types = [
      'node' => 'Nodes',
      'user' => 'Users',
      'taxonomy_term' => 'Taxonomy Terms',
      'comment' => 'Comments',
      'file' => 'Files',
      'media' => 'Media',
    ];
    
    foreach ($entity_types as $entity_type => $label) {
      try {
        $count = $this->getEntityCount($entity_type);
        $volume[$entity_type] = [
          'label' => $label,
          'count' => $count,
          'recent_count' => $this->getRecentEntityCount($entity_type),
          'size_estimate' => $this->getEntitySizeEstimate($entity_type),
        ];
      }
      catch (\Exception $e) {
        $volume[$entity_type] = [
          'label' => $label,
          'count' => 0,
          'recent_count' => 0,
          'size_estimate' => 0,
        ];
      }
    }
    
    return $volume;
  }

  /**
   * Gets total entity count.
   */
  protected function getEntityCount($entity_type) {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $query = $storage->getQuery()->accessCheck(FALSE)->count();
      return (int) $query->execute();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets recent entity count (last 30 days).
   */
  protected function getRecentEntityCount($entity_type, $days = 30) {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      
      if ($entity_type_definition->hasKey('created')) {
        $created_field = $entity_type_definition->getKey('created');
        $timestamp = strtotime('-' . $days . ' days');
        
        $query = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition($created_field, $timestamp, '>')
          ->count();
        
        return (int) $query->execute();
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Estimates entity storage size.
   */
  protected function getEntitySizeEstimate($entity_type) {
    try {
      // This is a rough estimate based on table sizes
      $tables = $this->getEntityTables($entity_type);
      $total_size = 0;
      
      foreach ($tables as $table) {
        $size = $this->getTableSize($table);
        $total_size += $size;
      }
      
      return $total_size;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets tables for an entity type.
   */
  protected function getEntityTables($entity_type) {
    $tables = [];
    
    // Base tables
    $tables[] = $entity_type . '_field_data';
    $tables[] = $entity_type . '_field_revision';
    
    // Field tables
    try {
      $field_map = $this->entityFieldManager->getFieldMap();
      if (isset($field_map[$entity_type])) {
        foreach ($field_map[$entity_type] as $field_name => $field_info) {
          $tables[] = $entity_type . '__' . $field_name;
          $tables[] = $entity_type . '_revision__' . $field_name;
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $tables;
  }

  /**
   * Gets table size in bytes.
   */
  protected function getTableSize($table_name) {
    try {
      $driver = $this->database->driver();
      
      if ($driver === 'mysql') {
        $query = "SELECT (data_length + index_length) AS size 
                  FROM information_schema.tables 
                  WHERE table_schema = DATABASE() AND table_name = :table";
        $result = $this->database->query($query, [':table' => $table_name])->fetchField();
        return (int) $result;
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return 0;
  }

  /**
   * Analyzes media usage.
   */
  protected function analyzeMediaUsage() {
    $media_analysis = [
      'total_files' => 0,
      'total_size' => 0,
      'file_types' => [],
      'storage_locations' => [],
      'media_types' => [],
      'unused_files' => 0,
    ];
    
    try {
      // Analyze files
      $files = $this->database->select('file_managed', 'f')
        ->fields('f', ['fid', 'filename', 'uri', 'filemime', 'filesize', 'status'])
        ->execute()
        ->fetchAll();
      
      $media_analysis['total_files'] = count($files);
      
      foreach ($files as $file) {
        $media_analysis['total_size'] += $file->filesize;
        
        // File types
        $mime_type = $file->filemime;
        if (!isset($media_analysis['file_types'][$mime_type])) {
          $media_analysis['file_types'][$mime_type] = [
            'count' => 0,
            'size' => 0,
          ];
        }
        $media_analysis['file_types'][$mime_type]['count']++;
        $media_analysis['file_types'][$mime_type]['size'] += $file->filesize;
        
        // Storage locations
        $scheme = \Drupal::service('stream_wrapper_manager')->getScheme($file->uri);
        if (!$scheme) {
          $scheme = 'unknown';
        }
        if (!isset($media_analysis['storage_locations'][$scheme])) {
          $media_analysis['storage_locations'][$scheme] = [
            'count' => 0,
            'size' => 0,
          ];
        }
        $media_analysis['storage_locations'][$scheme]['count']++;
        $media_analysis['storage_locations'][$scheme]['size'] += $file->filesize;
      }
      
      // Analyze media types if media module exists
      if ($this->entityTypeManager->hasDefinition('media_type')) {
        $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
        foreach ($media_types as $type_id => $type) {
          $count = $this->getContentCount('media', $type_id);
          $media_analysis['media_types'][$type_id] = [
            'label' => $type->label(),
            'count' => $count,
            'source' => $type->getSource()->getPluginId(),
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $media_analysis;
  }

  /**
   * Analyzes taxonomy structure.
   */
  protected function analyzeTaxonomyStructure() {
    $taxonomy_analysis = [];
    
    try {
      $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
      
      foreach ($vocabularies as $vid => $vocabulary) {
        $term_count = $this->getContentCount('taxonomy_term', $vid);
        $hierarchy_depth = $this->getTaxonomyHierarchyDepth($vid);
        
        $taxonomy_analysis[$vid] = [
          'id' => $vid,
          'label' => $vocabulary->label(),
          'description' => $vocabulary->getDescription(),
          'term_count' => $term_count,
          'hierarchy_depth' => $hierarchy_depth,
          'fields' => $this->getVocabularyFields($vid),
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $taxonomy_analysis;
  }

  /**
   * Gets taxonomy hierarchy depth.
   */
  protected function getTaxonomyHierarchyDepth($vid) {
    try {
      $query = "SELECT MAX(depth) FROM (
        WITH RECURSIVE term_hierarchy AS (
          SELECT tid, parent_target_id, 0 as depth
          FROM taxonomy_term__parent ttp
          INNER JOIN taxonomy_term_field_data ttfd ON ttp.entity_id = ttfd.tid
          WHERE ttfd.vid = :vid AND (ttp.parent_target_id = 0 OR ttp.parent_target_id IS NULL)
          
          UNION ALL
          
          SELECT ttp.tid, ttp.parent_target_id, th.depth + 1
          FROM taxonomy_term__parent ttp
          INNER JOIN term_hierarchy th ON ttp.parent_target_id = th.tid
          INNER JOIN taxonomy_term_field_data ttfd ON ttp.entity_id = ttfd.tid
          WHERE ttfd.vid = :vid
        )
        SELECT depth FROM term_hierarchy
      ) as depths";
      
      return (int) $this->database->query($query, [':vid' => $vid])->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets fields for a vocabulary.
   */
  protected function getVocabularyFields($vid) {
    $fields = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $vid);
    $field_info = [];
    
    foreach ($fields as $field_name => $field) {
      if (!$field->getFieldStorageDefinition()->isBaseField()) {
        $field_info[$field_name] = [
          'label' => $field->getLabel(),
          'type' => $field->getType(),
          'required' => $field->isRequired(),
        ];
      }
    }
    
    return $field_info;
  }

  /**
   * Analyzes menu structure.
   */
  protected function analyzeMenuStructure() {
    $menu_analysis = [];
    
    try {
      $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
      
      foreach ($menus as $menu_id => $menu) {
        $link_count = $this->getMenuLinkCount($menu_id);
        $max_depth = $this->getMenuMaxDepth($menu_id);
        
        $menu_analysis[$menu_id] = [
          'id' => $menu_id,
          'label' => $menu->label(),
          'description' => $menu->getDescription(),
          'link_count' => $link_count,
          'max_depth' => $max_depth,
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $menu_analysis;
  }

  /**
   * Gets menu link count.
   */
  protected function getMenuLinkCount($menu_id) {
    try {
      return (int) $this->database->select('menu_link_content_data', 'm')
        ->condition('menu_name', $menu_id)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets menu maximum depth.
   */
  protected function getMenuMaxDepth($menu_id) {
    try {
      // Count slashes in route to determine depth
      $query = $this->database->select('menu_link_content_data', 'm')
        ->condition('menu_name', $menu_id);
      $query->addExpression('MAX(CHAR_LENGTH(link__uri) - CHAR_LENGTH(REPLACE(link__uri, "/", "")))', 'max_depth');
      
      return (int) $query->execute()->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Analyzes block usage.
   */
  protected function analyzeBlockUsage() {
    $block_analysis = [
      'total_blocks' => 0,
      'custom_blocks' => 0,
      'views_blocks' => 0,
      'system_blocks' => 0,
      'block_types' => [],
    ];
    
    try {
      $blocks = $this->entityTypeManager->getStorage('block')->loadMultiple();
      $block_analysis['total_blocks'] = count($blocks);
      
      foreach ($blocks as $block) {
        $plugin_id = $block->getPluginId();
        
        if (strpos($plugin_id, 'block_content:') === 0) {
          $block_analysis['custom_blocks']++;
        }
        elseif (strpos($plugin_id, 'views_block:') === 0) {
          $block_analysis['views_blocks']++;
        }
        else {
          $block_analysis['system_blocks']++;
        }
        
        // Count by plugin type
        $plugin_base = explode(':', $plugin_id)[0];
        if (!isset($block_analysis['block_types'][$plugin_base])) {
          $block_analysis['block_types'][$plugin_base] = 0;
        }
        $block_analysis['block_types'][$plugin_base]++;
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $block_analysis;
  }

  /**
   * Analyzes user roles and permissions.
   */
  protected function analyzeUserRoles() {
    $role_analysis = [];
    
    try {
      $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
      
      foreach ($roles as $role_id => $role) {
        $user_count = $this->getUserCountByRole($role_id);
        $permissions = $role->getPermissions();
        
        $role_analysis[$role_id] = [
          'id' => $role_id,
          'label' => $role->label(),
          'weight' => $role->getWeight(),
          'user_count' => $user_count,
          'permission_count' => count($permissions),
          'permissions' => $permissions,
        ];
      }
    }
    catch (\Exception $e) {
      // Continue
    }
    
    return $role_analysis;
  }

  /**
   * Gets user count by role.
   */
  protected function getUserCountByRole($role_id) {
    try {
      return (int) $this->database->select('user__roles', 'ur')
        ->condition('roles_target_id', $role_id)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Finds deprecated fields.
   */
  protected function findDeprecatedFields() {
    $deprecated = [];
    $field_types = $this->analyzeFieldTypes();
    
    foreach ($field_types as $type => $info) {
      if ($info['is_deprecated']) {
        $deprecated[$type] = $info;
      }
    }
    
    return $deprecated;
  }

  /**
   * Gets field usage statistics.
   */
  protected function getFieldUsageStats() {
    $stats = [
      'most_used_field_types' => [],
      'unused_fields' => [],
      'required_fields' => [],
    ];
    
    $field_types = $this->analyzeFieldTypes();
    
    // Sort by usage count
    uasort($field_types, function ($a, $b) {
      return $b['count'] - $a['count'];
    });
    
    $stats['most_used_field_types'] = array_slice($field_types, 0, 10, true);
    
    return $stats;
  }

  /**
   * Gets display modes for an entity type and bundle.
   */
  protected function getDisplayModes($entity_type, $bundle) {
    try {
      $display_modes = $this->entityTypeManager->getStorage('entity_view_display')
        ->loadByProperties([
          'targetEntityType' => $entity_type,
          'bundle' => $bundle,
        ]);
      
      return array_keys($display_modes);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets form modes for an entity type and bundle.
   */
  protected function getFormModes($entity_type, $bundle) {
    try {
      $form_modes = $this->entityTypeManager->getStorage('entity_form_display')
        ->loadByProperties([
          'targetEntityType' => $entity_type,
          'bundle' => $bundle,
        ]);
      
      return array_keys($form_modes);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets workflow states for a content type.
   */
  protected function getWorkflowStates($bundle) {
    // This would integrate with workflow modules like Content Moderation
    // For now, return basic published/unpublished info
    try {
      $published = $this->database->select('node_field_data', 'n')
        ->condition('type', $bundle)
        ->condition('status', 1)
        ->countQuery()
        ->execute()
        ->fetchField();
      
      $unpublished = $this->database->select('node_field_data', 'n')
        ->condition('type', $bundle)
        ->condition('status', 0)
        ->countQuery()
        ->execute()
        ->fetchField();
      
      return [
        'published' => (int) $published,
        'unpublished' => (int) $unpublished,
      ];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Analyzes all entity types (site_audit functionality).
   */
  protected function analyzeAllEntityTypes() {
    $analysis = [
      'content_entities' => [],
      'config_entities' => [],
      'total_content_entities' => 0,
      'total_config_entities' => 0,
      'entity_summary' => [],
    ];

    try {
      $entity_type_definitions = $this->entityTypeManager->getDefinitions();
      
      foreach ($entity_type_definitions as $entity_type_id => $definition) {
        $entity_info = [
          'id' => $entity_type_id,
          'label' => $definition->getLabel(),
          'class' => $definition->getClass(),
          'group' => $definition->getGroup(),
          'provider' => $definition->getProvider(),
        ];
        
        if ($definition->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
          $analysis['content_entities'][$entity_type_id] = $entity_info;
          $analysis['total_content_entities']++;
        }
        else {
          $analysis['config_entities'][$entity_type_id] = $entity_info;
          $analysis['total_config_entities']++;
        }
      }
      
      // Create summary
      $analysis['entity_summary'] = [
        'total_entities' => count($entity_type_definitions),
        'content_entities' => $analysis['total_content_entities'],
        'config_entities' => $analysis['total_config_entities'],
      ];
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Analyzes content entity types in detail (site_audit functionality).
   */
  protected function analyzeContentEntityTypesDetailed() {
    $analysis = [
      'entity_counts' => [],
      'bundle_counts' => [],
      'total_content_items' => 0,
      'recommendations' => [],
    ];

    try {
      $all_bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
      
      foreach ($all_bundles as $entity_type => $bundles) {
        $entity_definition = $this->entityTypeManager->getDefinition($entity_type);
        $bundle_column_name = $entity_definition->getKey('bundle');
        $interfaces = class_implements($entity_definition->getClass());
        
        if ($bundle_column_name !== FALSE && in_array("Drupal\\Core\\Entity\\ContentEntityInterface", $interfaces)) {
          $analysis['entity_counts'][$entity_type] = 0;
          
          foreach ($bundles as $bundle => $info) {
            if (get_class($this->entityTypeManager->getStorage($entity_type)) != 'Drupal\Core\Entity\ContentEntityNullStorage') {
              $query = \Drupal::entityQuery($entity_type)
                ->accessCheck(FALSE)
                ->condition($bundle_column_name, $bundle)
                ->count();
              $field_count = $query->execute();
              
              $label = $info['label'];
              if (is_object($label)) {
                $label = (string) $label;
              }
              
              $analysis['bundle_counts'][$entity_type][$label] = $field_count;
              $analysis['entity_counts'][$entity_type] += $field_count;
              $analysis['total_content_items'] += $field_count;
              
              if ($field_count == 0) {
                $analysis['unused_bundles'][$entity_type][] = $label;
              }
            }
          }
        }
      }
      
      // Generate recommendations
      if (isset($analysis['unused_bundles']) && !empty($analysis['unused_bundles'])) {
        $total_unused = 0;
        foreach ($analysis['unused_bundles'] as $entity_type => $bundles) {
          $total_unused += count($bundles);
        }
        $analysis['recommendations'][] = "Found {$total_unused} unused content bundles. Consider removing unused bundles to reduce complexity.";
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Finds unused content types.
   */
  protected function findUnusedContentTypes() {
    $analysis = [
      'unused_types' => [],
      'total_unused' => 0,
      'recommendations' => [],
    ];

    try {
      $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      
      foreach ($node_types as $type_id => $type) {
        $count = $this->getContentCount('node', $type_id);
        
        if ($count == 0) {
          $analysis['unused_types'][] = [
            'id' => $type_id,
            'label' => $type->label(),
            'description' => $type->getDescription(),
          ];
        }
      }
      
      $analysis['total_unused'] = count($analysis['unused_types']);
      
      if ($analysis['total_unused'] > 0) {
        $analysis['recommendations'][] = "Found {$analysis['total_unused']} unused content types. Consider removing them to reduce administrative overhead.";
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Finds unused vocabularies.
   */
  protected function findUnusedVocabularies() {
    $analysis = [
      'unused_vocabularies' => [],
      'total_unused' => 0,
      'recommendations' => [],
    ];

    try {
      $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
      
      foreach ($vocabularies as $vid => $vocabulary) {
        $count = $this->getContentCount('taxonomy_term', $vid);
        
        if ($count == 0) {
          $analysis['unused_vocabularies'][] = [
            'id' => $vid,
            'label' => $vocabulary->label(),
            'description' => $vocabulary->getDescription(),
          ];
        }
      }
      
      $analysis['total_unused'] = count($analysis['unused_vocabularies']);
      
      if ($analysis['total_unused'] > 0) {
        $analysis['recommendations'][] = "Found {$analysis['total_unused']} unused vocabularies. Consider removing them to reduce complexity.";
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Analyzes entity bundles comprehensively.
   */
  protected function analyzeEntityBundles() {
    $analysis = [
      'bundle_summary' => [],
      'field_analysis' => [],
      'bundle_usage' => [],
      'recommendations' => [],
    ];

    try {
      $all_bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
      
      foreach ($all_bundles as $entity_type => $bundles) {
        $analysis['bundle_summary'][$entity_type] = [
          'total_bundles' => count($bundles),
          'bundles' => [],
        ];
        
        foreach ($bundles as $bundle => $info) {
          $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
          $field_count = 0;
          $custom_field_count = 0;
          
          foreach ($fields as $field) {
            $field_count++;
            if (!$field->getFieldStorageDefinition()->isBaseField()) {
              $custom_field_count++;
            }
          }
          
          $content_count = $this->getContentCount($entity_type, $bundle);
          
          $analysis['bundle_summary'][$entity_type]['bundles'][$bundle] = [
            'label' => is_object($info['label']) ? (string) $info['label'] : $info['label'],
            'total_fields' => $field_count,
            'custom_fields' => $custom_field_count,
            'content_count' => $content_count,
            'usage_status' => $content_count > 0 ? 'used' : 'unused',
          ];
          
          $analysis['bundle_usage'][$entity_type][$bundle] = $content_count;
        }
      }
      
      // Generate recommendations
      $total_unused_bundles = 0;
      foreach ($analysis['bundle_summary'] as $entity_type => $summary) {
        foreach ($summary['bundles'] as $bundle => $info) {
          if ($info['usage_status'] === 'unused') {
            $total_unused_bundles++;
          }
        }
      }
      
      if ($total_unused_bundles > 5) {
        $analysis['recommendations'][] = "Found {$total_unused_bundles} unused bundles across all entity types. Consider cleanup to improve site performance.";
      }
    }
    catch (\Exception $e) {
      $analysis['error'] = $e->getMessage();
    }

    return $analysis;
  }

  /**
   * Generates site_audit specific content recommendations.
   */
  protected function generateSiteAuditContentRecommendations() {
    $recommendations = [];
    
    // Collect recommendations from site_audit content analyses
    $entity_types = $this->analyzeContentEntityTypesDetailed();
    $unused_types = $this->findUnusedContentTypes();
    $unused_vocabularies = $this->findUnusedVocabularies();
    $bundles = $this->analyzeEntityBundles();
    
    $analyses = [$entity_types, $unused_types, $unused_vocabularies, $bundles];
    
    foreach ($analyses as $analysis) {
      if (isset($analysis['recommendations'])) {
        foreach ($analysis['recommendations'] as $recommendation) {
          $recommendations[] = [
            'type' => 'info',
            'category' => 'content',
            'message' => $recommendation,
            'priority' => 'low',
          ];
        }
      }
    }
    
    // Add specific recommendations based on analysis
    if ($unused_types['total_unused'] > 3) {
      $recommendations[] = [
        'type' => 'warning',
        'category' => 'content_cleanup',
        'message' => "High number of unused content types ({$unused_types['total_unused']}). Consider removing unused types to reduce administrative complexity.",
        'priority' => 'medium',
      ];
    }
    
    if ($unused_vocabularies['total_unused'] > 2) {
      $recommendations[] = [
        'type' => 'warning',
        'category' => 'taxonomy_cleanup',
        'message' => "Multiple unused vocabularies ({$unused_vocabularies['total_unused']}). Consider removing unused vocabularies.",
        'priority' => 'medium',
      ];
    }
    
    // Check for large content volumes
    if ($entity_types['total_content_items'] > 100000) {
      $recommendations[] = [
        'type' => 'info',
        'category' => 'content_volume',
        'message' => "Large content volume ({$entity_types['total_content_items']} items). Consider implementing content archiving strategies.",
        'priority' => 'low',
      ];
    }
    
    return $recommendations;
  }

}