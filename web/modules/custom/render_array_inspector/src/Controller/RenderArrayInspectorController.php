<?php

namespace Drupal\render_array_inspector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Render Array Inspector.
 */
class RenderArrayInspectorController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a RenderArrayInspectorController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Returns the render array structure for the current page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response containing the render array structure.
   */
  public function getRenderArray(Request $request) {
    $response = new AjaxResponse();
    
    // Add CORS headers for AJAX requests.
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
    
    // Log for debugging.
    \Drupal::logger('render_array_inspector')->info('getRenderArray called');

    try {
      // Get basic page information.
      $route_match = \Drupal::routeMatch();
      $route_name = $route_match->getRouteName();
      $current_path = \Drupal::service('path.current')->getPath();
      $current_user = \Drupal::currentUser();
      
      // Log for debugging.
      \Drupal::logger('render_array_inspector')->info('Generating render array for route: @route', ['@route' => $route_name]);
      
      // Create a simple data structure for demonstration.
      $main_content = [
        'page' => [
          'route' => $route_name ?: 'Unknown',
          'path' => $current_path,
        ],
        'render' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['page-content']],
          '#markup' => 'Page content',
        ],
        'data' => [
          'string' => 'Hello World',
          'number' => 42,
          'boolean' => TRUE,
          'null' => NULL,
          'array' => [
            'item1' => 'First item',
            'item2' => 'Second item',
          ],
        ],
      ];

      // Get configuration for display options.
      $config = \Drupal::config('render_array_inspector.settings');
      $max_depth = $config->get('max_depth') ?: 10;
      
      // Format the render array for display.
      try {
        $formatted_html = $this->formatRenderArray($main_content, 0, $max_depth);
      } catch (\Exception $format_error) {
        // Fallback to simple display.
        $formatted_html = '<li>Error formatting data: ' . htmlspecialchars($format_error->getMessage()) . '</li>';
        $formatted_html .= '<li>Raw data: <pre>' . htmlspecialchars(print_r($main_content, TRUE)) . '</pre></li>';
      }

      // Create simple, readable output.
      $final_html = $this->buildSimplePageInfo($route_name, $current_path, $current_user);
      $final_html .= $this->buildSimpleDump($main_content);
      
      // Log the output for debugging.
      \Drupal::logger('render_array_inspector')->info('Generated HTML length: @length', ['@length' => strlen($final_html)]);
      
      $response->addCommand(new HtmlCommand('#data-display', $final_html));

    } catch (\Exception $e) {
      $error_html = '<div class="message message-error">';
      $error_html .= '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
      $error_html .= '<br><small>File: ' . basename($e->getFile()) . ' Line: ' . $e->getLine() . '</small>';
      $error_html .= '</div>';
      $response->addCommand(new HtmlCommand('#data-display', $error_html));
    }

    return $response;
  }

  /**
   * Formats a data structure for display in a tree structure.
   *
   * @param mixed $data
   *   The data to format.
   * @param int $depth
   *   The current depth level.
   * @param int $max_depth
   *   The maximum depth to traverse.
   *
   * @return string
   *   The formatted HTML string.
   */
  protected function formatRenderArray($data, $depth = 0, $max_depth = 10) {
    if ($depth > $max_depth) {
      return '<li class="max-depth">... (max depth reached)</li>';
    }

    $output = '';
    
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        // Skip certain keys that are not useful for inspection.
        if (in_array($key, ['#printed'], TRUE)) {
          continue;
        }

        $key_display = htmlspecialchars((string) $key);
        $key_class = strpos($key, '#') === 0 ? 'render-property' : 'data-key';
        
        if (is_array($value)) {
          if (count($value) > 0) {
            // Has children - make it collapsible.
            $children_html = $this->formatRenderArray($value, $depth + 1, $max_depth);
            $output .= '<li>';
            $output .= '<span class="render-array-key array-with-children ' . $key_class . '" data-key="' . $key_display . '">' . $key_display . ' <span class="array-count">(' . count($value) . ' items)</span></span>';
            $output .= '<ul class="render-array-children collapsed">' . $children_html . '</ul>';
            $output .= '</li>';
          } else {
            // Empty array.
            $output .= '<li><span class="render-array-key scalar ' . $key_class . '">' . $key_display . ': <span class="render-array-value empty-array">[Empty Array]</span></span></li>';
          }
        } else {
          // Scalar value.
          $value_info = $this->formatScalarValue($value);
          $output .= '<li><span class="render-array-key scalar ' . $key_class . '">' . $key_display . ': <span class="render-array-value ' . $value_info['type'] . '">' . $value_info['display'] . '</span></span></li>';
        }
      }
    } else {
      // Non-array data.
      $value_info = $this->formatScalarValue($data);
      $output .= '<li><span class="render-array-value ' . $value_info['type'] . '">' . $value_info['display'] . '</span></li>';
    }

    return $output;
  }
  
  /**
   * Formats a scalar value for display.
   *
   * @param mixed $value
   *   The value to format.
   *
   * @return array
   *   Array with 'display' and 'type' keys.
   */
  protected function formatScalarValue($value) {
    if (is_null($value)) {
      return ['display' => 'NULL', 'type' => 'null'];
    }
    
    if (is_bool($value)) {
      return ['display' => $value ? 'TRUE' : 'FALSE', 'type' => 'boolean'];
    }
    
    if (is_numeric($value)) {
      return ['display' => (string) $value, 'type' => 'numeric'];
    }
    
    if (is_string($value)) {
      $display = htmlspecialchars($value);
      if (strlen($display) > 100) {
        $display = substr($display, 0, 100) . '...';
      }
      return ['display' => '"' . $display . '"', 'type' => 'string'];
    }
    
    if (is_object($value)) {
      return ['display' => '[Object: ' . get_class($value) . ']', 'type' => 'object'];
    }
    
    return ['display' => '[' . gettype($value) . ']', 'type' => 'unknown'];
  }
  
  /**
   * Build simple page information.
   */
  protected function buildSimplePageInfo($route_name, $current_path, $current_user) {
    $html = '<div class="page-info">';
    $html .= '<h4>Page Info</h4>';
    $html .= '<div class="info-line"><span class="info-label">Route:</span> <span class="info-value">' . htmlspecialchars($route_name ?: 'Unknown') . '</span></div>';
    $html .= '<div class="info-line"><span class="info-label">Path:</span> <span class="info-value">' . htmlspecialchars($current_path) . '</span></div>';
    $html .= '</div>';
    return $html;
  }
  
  /**
   * Build simple dump display.
   */
  protected function buildSimpleDump($data) {
    $html = '<div class="tree-container">';
    $html .= '<div class="tree-header">Render Array</div>';
    $html .= '<div class="tree-content">';
    $html .= '<div class="simple-dump">';
    $html .= $this->formatAsSimpleDump($data, 0);
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }
  
  /**
   * Format data as a simple, readable dump.
   */
  protected function formatAsSimpleDump($data, $indent = 0) {
    $output = '';
    $spaces = str_repeat('  ', $indent);
    
    if (is_array($data)) {
      $output .= "Array (\n";
      foreach ($data as $key => $value) {
        $key_class = strpos($key, '#') === 0 ? 'dump-array' : 'dump-key';
        $output .= $spaces . '  <span class="' . $key_class . '">[' . htmlspecialchars($key) . ']</span> => ';
        
        if (is_array($value)) {
          $output .= $this->formatAsSimpleDump($value, $indent + 1);
        } else {
          $output .= $this->formatSimpleValue($value);
        }
        $output .= "\n";
      }
      $output .= $spaces . ')';
    } else {
      $output .= $this->formatSimpleValue($data);
    }
    
    return $output;
  }
  
  /**
   * Format a simple value with appropriate styling.
   */
  protected function formatSimpleValue($value) {
    if (is_null($value)) {
      return '<span class="dump-null">NULL</span>';
    }
    
    if (is_bool($value)) {
      return '<span class="dump-boolean">' . ($value ? 'TRUE' : 'FALSE') . '</span>';
    }
    
    if (is_numeric($value)) {
      return '<span class="dump-number">' . $value . '</span>';
    }
    
    if (is_string($value)) {
      $escaped = htmlspecialchars($value);
      if (strlen($escaped) > 100) {
        $escaped = substr($escaped, 0, 100) . '...';
      }
      return '<span class="dump-string">"' . $escaped . '"</span>';
    }
    
    return '<span class="dump-other">' . htmlspecialchars(gettype($value)) . '</span>';
  }

}