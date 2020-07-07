<?php

namespace Drupal\fidesio_rss\Plugin\QueueWorker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Save queue item in a node(article).
 *
 * @QueueWorker(
 *   id = "rss_import_queue",
 *   title = @Translation("Import Content From RSS URL in the config"),
 *   cron = {"time" = 5}
 * )
 */
class RssQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $title = isset($item->title) && $item->title ? $item->title : NULL;
    $body = isset($item->body) && $item->body ? $item->body : NULL;
    $created = isset($item->created) && $item->created ? strtotime($item->created) : NULL;

    try {
      // Check if we have a title and a body.
      if (!$title || !$body) {
        throw new \Exception('Missing Title or Body');
      }

      $storage = $this->entityTypeManager->getStorage('node');
      $node = $storage->create(
        [
          'type' => 'article_rss',
          'title' => $title,
          'body' => [
            'value' => $body,
            'format' => 'basic_html',
          ],
          'created' => $created,
        ]
      );
      $node->save();

      // Log in the watchdog for debugging purpose.
      $this->loggerChannelFactory->get('debug')
        ->debug('Create node @id from queue %item',
          [
            '@id' => $node->id(),
            '%item' => print_r($item, TRUE),
          ]);
    }
    catch (\Exception $e) {
      $this->loggerChannelFactory->get('Warning')
        ->warning('Exception trow for queue @error',
          ['@error' => $e->getMessage()]);
    }

  }

}