<?php

namespace Drupal\project_browser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\InfoParserException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\project_browser\EnabledSourceHandler;
use Drupal\project_browser\Plugin\ProjectBrowserSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Defines a controller to provide the Project Browser UI.
 *
 * @internal
 *   Controller classes are internal.
 */
class BrowserController extends ControllerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The EnabledSourceHandler.
   *
   * @var \Drupal\project_browser\EnabledSourceHandler
   */
  protected $enabledSource;

  /**
   * ProjectBrowser cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBin;

  /**
   * Build the project browser controller.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\project_browser\EnabledSourceHandler $enabled_source
   *   The enabled source.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ModuleExtensionList $module_list, RequestStack $request_stack, EnabledSourceHandler $enabled_source, MessengerInterface $messenger) {
    $this->moduleHandler = $module_handler;
    $this->moduleList = $module_list;
    $this->requestStack = $request_stack;
    $this->enabledSource = $enabled_source;
    $this->cacheBin = $this->cache('project_browser');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('extension.list.module'),
      $container->get('request_stack'),
      $container->get('project_browser.enabled_source'),
      $container->get('messenger'),
    );
  }

  /**
   * Builds the browse page.
   *
   * @return array
   *   A render array.
   */
  public function browse() {
    $modules_status = $this->getModuleStatuses();
    $request = $this->requestStack->getCurrentRequest();
    $current_source = $this->enabledSource->getCurrentSource();

    if ($current_source->getPluginId() === 'drupalorg_mockapi') {
      $this->messenger
        ->addStatus($this->t('Project Browser is currently a prototype, and the projects listed may not be up to date with Drupal.org. For the most updated list of projects, please visit <a href=":url">:url</a>', [':url' => 'https://www.drupal.org/project/project_module']))
        ->addStatus($this->t('Your feedback and input are welcome at <a href=":url">:url</a>', [':url' => 'https://www.drupal.org/project/issues/project_browser']));
    }

    return [
      '#theme' => 'project_browser_main_app',
      '#attached' => [
        'library' => [
          'project_browser/svelte',
        ],
        'drupalSettings' => [
          'project_browser' => [
            'modules' => $modules_status,
            'drupal_version' => \Drupal::VERSION,
            'drupal_core_compatibility' => \Drupal::CORE_COMPATIBILITY,
            'module_path' => $this->moduleHandler->getModule('project_browser')->getPath(),
            'origin_url' => $request->getSchemeAndHttpHost() . $request->getBaseUrl(),
            'special_ids' => $this->getSpecialIds(),
            'sort_options' => array_values($current_source->getSortOptions()),
            'maintenance_options' => $current_source->getMaintenanceOptions(),
            'security_options' => $current_source->getSecurityOptions(),
            'development_options' => $current_source->getDevelopmentOptions(),
          ],
        ],
      ],
    ];
  }

  /**
   * Return special IDs for some vocabularies.
   *
   * This is needed because these two vocabularies have a special term
   * in them that shows an icon next to the label, so we need to be
   * explicit about these special cases.
   *
   * @return array
   *   List of special IDs per vocabulary.
   */
  protected function getSpecialIds(): array {
    return [
      'maintenance_status' => [
        'id' => ProjectBrowserSourceBase::MAINTAINED_ID,
        'name' => $this->t('@maintained_label', ['@maintained_label' => ProjectBrowserSourceBase::MAINTAINED_LABEL]),
      ],
      'security_coverage' => [
        'id' => ProjectBrowserSourceBase::COVERED_ID,
        'name' => $this->t('@covered_label', ['@covered_label' => ProjectBrowserSourceBase::COVERED_LABEL]),
      ],
      'all_values' => ProjectBrowserSourceBase::ALL_VALUES_ID,
    ];
  }

  /**
   * Gets all module statuses.
   *
   * @return array
   *   An array of module statues, keyed by machine name.
   */
  protected function getModuleStatuses(): array {
    // Sort all modules by their names.
    try {
      // The module list needs to be reset so that it can re-scan and include
      // any new modules that may have been added directly into the filesystem.
      $modules = $this->moduleList->reset()->getList();
      uasort($modules, [ModuleExtensionList::class, 'sortByName']);
    }
    catch (InfoParserException $e) {
      $this->messenger()->addError($this->t('Modules could not be listed due to an error: %error', ['%error' => $e->getMessage()]));
      $modules = [];
    }

    return array_map(function ($value) {
      return $value->status;
    }, $modules);
  }

}
