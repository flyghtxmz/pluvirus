<?php

defined('ABSPATH') || exit;
?>
<div class="fcrs-panel" style="max-width: 760px;">
  <?php if ($title) : ?>
    <h2><?php echo esc_html($title); ?></h2>
  <?php endif; ?>

  <?php if ($description) : ?>
    <p><?php echo esc_html($description); ?></p>
  <?php endif; ?>

  <?php if ($show_config_box) : ?>
    <div style="padding:12px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
      <p><strong>Endpoint:</strong> <?php echo $config['endpoint'] ? esc_html($config['endpoint']) : 'Nao configurado'; ?></p>
      <p><strong>Chave configurada:</strong> <?php echo $config['secret'] ? 'Sim' : 'Nao'; ?></p>
      <p><strong>Modelo fixo:</strong> <?php echo $config['model'] ? esc_html($config['model']) : 'Nao definido'; ?></p>
      <p><strong>Timeout:</strong> <?php echo esc_html((string) $config['timeout']); ?> segundos</p>
    </div>
  <?php endif; ?>

  <?php if (!$config['ready']) : ?>
    <p>Defina as constantes do Cloud Run no <code>wp-config.php</code> antes de usar este plugin.</p>
  <?php endif; ?>

  <?php if (!empty($result['message'])) : ?>
    <div style="padding:12px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
      <p><strong>Status:</strong> <?php echo esc_html((string) $result['status']); ?></p>
      <p><?php echo esc_html((string) $result['message']); ?></p>
      <?php if (!empty($result['text'])) : ?>
        <p><?php echo esc_html((string) $result['text']); ?></p>
      <?php endif; ?>
      <?php if (!empty($result['attachment_id'])) : ?>
        <p>Biblioteca de midia: ID <?php echo esc_html((string) $result['attachment_id']); ?>.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($result['preview_url'])) : ?>
    <div style="margin:16px 0;">
      <p><strong>Preview da imagem gerada:</strong></p>
      <img src="<?php echo esc_url((string) $result['preview_url']); ?>" alt="Preview da imagem gerada" style="max-width:100%; height:auto; border:1px solid #dcdcde;">
    </div>
  <?php endif; ?>

  <div style="padding:16px; border:1px solid #dcdcde; background:#fff;">
    <form action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::ACTION); ?>">
      <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
      <?php wp_nonce_field(FCRS_Plugin::NONCE, 'fcrs_nonce'); ?>

      <p>
        <label for="fcrs-template"><strong>Template</strong></label><br>
        <select id="fcrs-template" name="template" required>
          <?php foreach ($templates as $template_key => $template_label) : ?>
            <option value="<?php echo esc_attr($template_key); ?>" <?php selected($selected_template, $template_key); ?>>
              <?php echo esc_html($template_label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <label for="fcrs-user-prompt"><strong>Prompt</strong></label><br>
        <textarea
          id="fcrs-user-prompt"
          name="userPrompt"
          rows="6"
          cols="60"
          placeholder="Descreva a imagem que voce quer gerar."
        ><?php echo esc_textarea($user_prompt); ?></textarea>
      </p>

      <p>
        <label for="fcrs-reference-image"><strong>Imagem de referencia</strong> (opcional)</label><br>
        <input id="fcrs-reference-image" type="file" name="referenceImage" accept="image/*">
      </p>

      <div data-fcrs-preview style="display:none; margin:12px 0;">
        <p><strong>Preview da imagem de referencia:</strong></p>
        <img data-fcrs-preview-image src="" alt="Preview da imagem de referencia" style="max-width:280px; height:auto; border:1px solid #dcdcde;">
      </div>

      <p>
        <label>
          <input type="checkbox" name="saveToMedia" value="1" <?php checked($save_to_media); ?>>
          Salvar tambem na biblioteca de midia
        </label>
      </p>

      <p>
        <button type="submit" <?php disabled(!$config['ready']); ?>>
          <?php echo esc_html($submit_label); ?>
        </button>
      </p>
    </form>
  </div>
</div>
