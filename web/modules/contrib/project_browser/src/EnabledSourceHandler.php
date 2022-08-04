<?php

namespace Drupal\project_browser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceInterface;
use Drupal\project_browser\Plugin\ProjectBrowserSourceManager;
use Psr\Log\LoggerInterface;

/**
 * Defines enabled source.
 */
class EnabledSourceHandler {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The ProjectBrowserSourceManager.
   *
   * @var \Drupal\project_browser\plugin\ProjectBrowserSourceManager
   */
  private $pluginManager;

  /**
   * EnabledSourceHandler constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\project_browser\plugin\ProjectBrowserSourceManager $plugin_manager
   *   The plugin manager.
   */
  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory, ProjectBrowserSourceManager $plugin_manager) {
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Returns a plugin instance corresponding to the enabled_source config.
   *
   * @return \Drupal\project_browser\Plugin\ProjectBrowserSourceInterface|null
   *   The Project Browser source plugin, or NULL.
   */
  public function getCurrentSource(): ?ProjectBrowserSourceInterface {
    $config = $this->configFactory->get('project_browser.admin_settings');
    $plugin_id = $config->get('enabled_source');
    if (!$this->pluginManager->hasDefinition($plugin_id)) {
      // Ignore if the plugin does not exist, but log it.
      $this->logger->warning('Project browser tried to load the enabled source %source, but the plugin does not exist. Make sure you have run update.php after updating the Project Browser module.', ['%source' => $plugin_id]);
      return NULL;
    }

    return $this->pluginManager->createInstance($plugin_id);
  }

}
