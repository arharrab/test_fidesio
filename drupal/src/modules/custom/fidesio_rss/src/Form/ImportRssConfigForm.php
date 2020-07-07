<?php
namespace Drupal\fidesio_rss\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure form settings rss url.
 */
class ImportRssConfigForm extends ConfigFormBase{

    /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'fidesio_rss.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fidesio_rss_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['rss_url'] = [
      '#type' => 'url',
      '#title' => $this->t('RSS URL'),
      '#default_value' => $config->get('rss_url'),
    ];  


    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('rss_url', $form_state->getValue('rss_url'))->save();

    parent::submitForm($form, $form_state);
  }
}