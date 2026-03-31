<?php

defined('ABSPATH') || exit;

$has_result_message = !empty($result['message']);
$is_processing = !empty($result['job_id']) && empty($result['preview_url']) && in_array((string) $result['status'], array('queued', 'processing'), true);
$preview_url = !empty($result['preview_url']) ? (string) $result['preview_url'] : '';
$download_url = !empty($download_action_url) ? (string) $download_action_url : '';
$download_filename = !empty($result['download_filename']) ? (string) $result['download_filename'] : 'imagem-gerada';
$text_parameter_maxlength = $template_uses_text_parameter
  ? (((int) $template_text_parameter['word_limit'] * (int) $template_text_parameter['letter_limit']) + max(0, ((int) $template_text_parameter['word_limit'] - 1)))
  : 0;
?>
<div
  class="fcrs-panel fcrs-generator"
  style="<?php echo esc_attr($template_color_style); ?>"
  data-fcrs-loading-messages="<?php echo esc_attr(wp_json_encode($ui_strings['loading_messages'])); ?>"
  data-fcrs-waiting-message="<?php echo esc_attr((string) $ui_strings['waiting_message']); ?>"
  data-fcrs-poll-failed-message="<?php echo esc_attr((string) $ui_strings['poll_failed_message']); ?>"
  data-fcrs-query-failed-message="<?php echo esc_attr((string) $ui_strings['query_failed_message']); ?>"
>
  <?php if ($title) : ?>
    <div class="fcrs-generator__header">
      <h2 class="fcrs-generator__title"><?php echo esc_html($title); ?></h2>
      <?php if (!empty($generator_description)) : ?>
        <div class="fcrs-generator__subtitle"><?php echo esc_html((string) $generator_description); ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div
    class="fcrs-status-card<?php echo $has_result_message ? '' : ' is-hidden'; ?><?php echo $is_processing ? ' is-loading' : ''; ?>"
    <?php if ($is_processing) : ?>
      data-fcrs-job-monitor
      data-job-id="<?php echo esc_attr((string) $result['job_id']); ?>"
      data-result-token="<?php echo esc_attr((string) $result_token); ?>"
    <?php endif; ?>
  >
    <span class="fcrs-visually-hidden" data-fcrs-job-status-text><?php echo esc_html((string) $result['status']); ?></span>
    <div class="fcrs-loading-popup<?php echo $is_processing ? '' : ' is-hidden'; ?>" data-fcrs-loading-popup>
      <div class="fcrs-loading-popup__spinner" aria-hidden="true"></div>
      <div class="fcrs-loading-popup__title"><?php echo esc_html((string) $ui_strings['loading_title']); ?></div>
      <div class="fcrs-loading-popup__message" data-fcrs-loading-message><?php echo esc_html((string) $ui_strings['loading_messages'][0]); ?></div>
    </div>
    <div class="fcrs-status-card__message" data-fcrs-job-message><?php echo esc_html((string) $result['message']); ?></div>
    <div class="fcrs-status-card__meta<?php echo (!empty($result['temporary_result']) || !empty($result['attachment_id'])) ? '' : ' is-hidden'; ?>" data-fcrs-job-meta>
      <?php if (!empty($result['temporary_result']) && empty($result['attachment_id'])) : ?>
        <div class="fcrs-status-chip"><?php echo esc_html((string) $ui_strings['temporary_result']); ?><?php if (!empty($result['result_url_expires_at'])) : ?> <?php echo esc_html((string) $ui_strings['until']); ?> <?php echo esc_html((string) $result['result_url_expires_at']); ?><?php endif; ?></div>
      <?php endif; ?>
      <?php if (!empty($result['attachment_id'])) : ?>
        <div class="fcrs-status-chip"><?php echo esc_html((string) $ui_strings['media_library']); ?>: ID <?php echo esc_html((string) $result['attachment_id']); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="fcrs-result-card<?php echo $preview_url ? '' : ' is-hidden'; ?>" data-fcrs-result-card>
    <div class="fcrs-result-card__media" data-fcrs-protected-result oncontextmenu="return false;">
      <div class="fcrs-result-card__image-frame<?php echo $preview_url ? ' has-image' : ''; ?>">
        <img
          data-fcrs-result-image
          class="fcrs-result-card__img"
          src="<?php echo $preview_url ? esc_url($preview_url) : ''; ?>"
          alt="<?php echo esc_attr((string) $ui_strings['result_image_label']); ?>"
          <?php echo $preview_url ? '' : 'style="display:none;"'; ?>
          draggable="false"
        >
      </div>
      <div class="fcrs-result-card__shield" aria-hidden="true"></div>
    </div>
    <div class="fcrs-result-card__actions">
      <?php if ($template_use_rewarded) : ?>
        <div class="fcrs-download-rewarded-note">
          <?php echo esc_html((string) $ui_strings['rewarded_download_notice']); ?>
        </div>
      <?php endif; ?>
      <a
        class="fcrs-download-button<?php echo $download_url ? '' : ' is-hidden'; ?>"
        data-fcrs-download-link
        href="<?php echo esc_url($download_url ? $download_url : '#'); ?>"
        data-url="<?php echo esc_url($download_url); ?>"
        data-download-url="<?php echo esc_url($download_url); ?>"
        data-download-filename="<?php echo esc_attr($download_filename); ?>"
        data-fcrs-rewarded-download="<?php echo $template_use_rewarded ? '1' : '0'; ?>"
        data-default-label="<?php echo esc_attr((string) $ui_strings['download_label']); ?>"
        data-retry-label="<?php echo esc_attr((string) $ui_strings['download_retry_label']); ?>"
        data-pending-label="<?php echo esc_attr((string) $ui_strings['rewarded_loading_label']); ?>"
        joinadscode="<?php echo $template_use_rewarded ? 'reward' : ''; ?>"
        download
      >
        <?php echo esc_html((string) $ui_strings['download_label']); ?>
      </a>
      <a class="fcrs-secondary-action<?php echo $preview_url ? '' : ' is-hidden'; ?>" data-fcrs-reset-link href="<?php echo esc_url($reset_url); ?>">
        <?php echo esc_html((string) $ui_strings['generate_again_label']); ?>
      </a>
    </div>
  </div>

  <div class="fcrs-form-card<?php echo ($preview_url || $is_processing) ? ' is-hidden' : ''; ?>" data-fcrs-form-card>
    <form class="fcrs-generator-form" action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::GENERATE_ACTION); ?>">
      <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
      <input type="hidden" name="generationType" value="image">
      <input type="hidden" name="generationContext" value="article">
      <input type="hidden" name="templateKey" value="<?php echo esc_attr((string) $article_config['template_key']); ?>">
      <input type="hidden" name="model" value="<?php echo esc_attr((string) $model_in_use); ?>">
      <?php wp_nonce_field(FCRS_Plugin::NONCE, 'fcrs_nonce'); ?>

      <?php if ($template_uses_text_parameter) : ?>
        <div class="fcrs-upload-card">
          <label class="fcrs-upload-card__label" for="fcrs-text-parameter-input">
            <?php echo esc_html((string) $ui_strings['parameter_input_label']); ?>
          </label>
          <input
            id="fcrs-text-parameter-input"
            class="fcrs-upload-card__input fcrs-text-parameter-input"
            type="text"
            name="templateParameterValue"
            value="<?php echo esc_attr($input_parameter_value); ?>"
            placeholder="<?php echo esc_attr((string) $ui_strings['parameter_input_placeholder']); ?>"
            maxlength="<?php echo esc_attr((string) $text_parameter_maxlength); ?>"
            data-fcrs-text-parameter-input
            data-parameter-label="<?php echo esc_attr((string) $template_text_parameter['label']); ?>"
            data-parameter-placeholder="<?php echo esc_attr((string) $template_text_parameter['placeholder']); ?>"
            data-word-limit="<?php echo esc_attr((string) $template_text_parameter['word_limit']); ?>"
            data-letter-limit="<?php echo esc_attr((string) $template_text_parameter['letter_limit']); ?>"
            data-required-message="<?php echo esc_attr((string) $ui_strings['parameter_required_message']); ?>"
            data-word-limit-message="<?php echo esc_attr((string) $ui_strings['parameter_word_limit_message']); ?>"
            data-letter-limit-message="<?php echo esc_attr((string) $ui_strings['parameter_letter_limit_message']); ?>"
            required
          >
        </div>
      <?php elseif (!$template_has_reference_image) : ?>
        <div class="fcrs-upload-card">
          <label class="fcrs-upload-card__label" for="fcrs-reference-image-front"><?php echo esc_html((string) $ui_strings['upload_label']); ?></label>
          <div class="fcrs-upload-card__hint">
            <?php echo esc_html($template_requires_reference ? (string) $ui_strings['upload_required'] : (string) $ui_strings['upload_optional']); ?>
          </div>
          <div class="fcrs-upload-card__subhint"><?php echo esc_html(sprintf((string) $ui_strings['upload_subhint'], (int) $max_reference_images)); ?></div>
          <input
            id="fcrs-reference-image-front"
            class="fcrs-upload-card__input"
            type="file"
            name="referenceImage[]"
            accept="image/*"
            data-fcrs-max-reference-images="<?php echo esc_attr((string) $max_reference_images); ?>"
            multiple
            <?php echo $template_requires_reference ? 'required' : ''; ?>
          >
        </div>

        <div class="fcrs-preview-strip is-hidden" data-fcrs-preview>
          <div class="fcrs-preview-strip__title"><?php echo esc_html((string) $ui_strings['selected_photos']); ?></div>
          <div class="fcrs-preview-strip__grid" data-fcrs-preview-grid></div>
        </div>
      <?php endif; ?>

      <div class="fcrs-generator-form__actions">
        <button
          type="submit"
          class="fcrs-generate-button"
          data-fcrs-submit
          <?php disabled($button_disabled || $is_processing); ?>
        >
          <?php echo esc_html($submit_label); ?>
        </button>
      </div>
    </form>
  </div>
</div>
