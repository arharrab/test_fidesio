<?php

namespace Drupal\fidesio_rss\Data;

use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;

class RssDataImport{
  
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Inject services.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Getdata from external source and create a item queue for each data.
   *
   * @return array
   *   Return string.
   */
  public function getData() {

    $data = $this->getDataFromRss();
    if (!$data) {
      return [
        '#type' => 'markup',
        '#markup' => t('No data found'),
      ];
    }

    $queue = \Drupal::queue('rss_import_queue');
    $queue->createQueue();

    $totalItemsBefore = $queue->numberOfItems();

    // 3. For each element of the array, create a new queue item.
    foreach ($data as $element) {
      // Create new queue item.
      $queue->createItem($element);
    }

    // 4. Get the total of item in the Queue.
    $totalItemsAfter = $queue->numberOfItems();

    // 5. Get what's in the queue now.
    $tableVariables = $this->getItemList($queue);
    

    $finalMessage = t('The Queue had @totalBefore items. We should have added @count items in the Queue. Now the Queue has @totalAfter items.',
      [
        '@count' => count($data),
        '@totalAfter' => $totalItemsAfter,
        '@totalBefore' => $totalItemsBefore,
      ]);

    return [
      '#type' => 'table',
      '#caption' => $finalMessage,
      '#header' => $tableVariables['header'],
      '#rows' => $tableVariables['rows'],
      '#attributes' => $tableVariables['attributes'],
      '#sticky' => $tableVariables['sticky'],
      'empty' => t('No items.'),
    ];
  }

  /**
   * Generate an array of objects from an external RSS file.
   *
   * @return array|bool
   *   Return an array or false
   */
  protected function getDataFromRss() {
    // 1. Try to get the data form the RSS
    // URI of the XML file.
    $config = \Drupal::service('config.factory')->getEditable('fidesio_rss.settings');
    $uri = $config->get('rss_url');

    try {
      $response = $this->client->get($uri, ['headers' => ['Accept' => 'text/plain']]);
      $data = (string) $response->getBody();
      if (empty($data)) {
        return FALSE;
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }

    // 2. Retrieve data in a simple xml object.
    $data = simplexml_load_string($data);

    // 3. Transform in a array of object
    // We could transform in array
    // $data = json_decode(json_encode($data));
    // Look at all children of the channel child.
    $content = [];
    foreach ($data->children()->children() as $child) {
      if (!empty($child->title)) {
        $item = new \stdClass();
        $item->title = $child->title->__toString();
        $item->body = $child->description->__toString();
        $item->created = $child->pubDate->__toString();
        $content[] = $item;
      }
    }

    if (empty($content)) {
      return FALSE;
    }

    return $content;
    
  }

  /**
   * Get all items of queue.
   *
   * Next place them in an array so we can retrieve them in a table.
   *
   * @param object $queue
   *   A queue object.
   *
   * @return array
   *   A table array for rendering.
   */
  protected function getItemList($queue) {
    $retrieved_items = [];
    $items = [];

    // Claim each item in queue.
    while ($item = $queue->claimItem()) {
      $retrieved_items[] = [
        'data' => [$item->data->title, $item->item_id],
      ];
      // Track item to release the lock.
      $items[] = $item;
    }

    // Release claims on items in queue.
    foreach ($items as $item) {
      $queue->releaseItem($item);
    }

    // Put the items in a table array for rendering.
    $tableTheme = [
      'header' => [t('Title'), t('ID')],
      'rows'   => $retrieved_items,
      'attributes' => [],
      'caption' => '',
      'colgroups' => [],
      'sticky' => TRUE,
      'empty' => t('No items.'),
    ];

    return $tableTheme;

  }
}