<?php

namespace Drupal\fidesio_rss\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "newest_content_block",
 *   admin_label = @Translation("Newest Content"),
 *   category = @Translation("Custom"),
 * )
 */
class NewestContent extends BlockBase implements BlockPluginInterface{

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $number = isset($config['newest_content_count']) ? $config['newest_content_count'] : NULL;
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'article_rss')
        ->condition('status', 1)
        ->sort('created' , 'DESC')
        ->range(0, $number);
    $nids = $query->execute();
    $datas = Node::loadMultiple($nids);
    $data = [];
    foreach($datas as $item){
        $data[] =[
            'title' => $item->title->value,
            'description' => $item->body->value,
        ];
    }
    

    $build['#class'] = 'last-rss-article';
    $build['#data'] =  $data;
    $build['#theme'] = 'block__last_rss_article';
    $build['#type'] = 'article_rss';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['newest_content_expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Expire every'),
      '#description' => $this->t('Define expire duration'),
      '#default_value' => isset($config['newest_content_expire']) ? $config['newest_content_expire'] : '',
    ];
    $form['newest_content_count'] = [
        '#type' => 'number',
        '#title' => $this->t('Number or article'),
        '#description' => $this->t('Define article number'),
        '#default_value' => isset($config['newest_content_count']) ? $config['newest_content_count'] : '',
      ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['newest_content_expire'] = $values['newest_content_expire'];
    $this->configuration['newest_content_count'] = $values['newest_content_count'];
  }

}