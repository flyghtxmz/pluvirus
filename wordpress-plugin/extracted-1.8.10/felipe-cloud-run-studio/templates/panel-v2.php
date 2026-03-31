<?php

defined('ABSPATH') || exit;
?>
<div class="fcrs-panel" style="max-width: 820px;">
  <?php if ($title) : ?>
    <h2><?php echo esc_html($title); ?></h2>
  <?php endif; ?>

  <?php if ($description) : ?>
    <p><?php echo esc_html($description); ?></p>
  <?php endif; ?>

  <?php if ($show_settings_form) : ?>
    <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
      <h3 style="margin-top:0;">Configuracao de conexao</h3>
      <p>Voce pode salvar a URL do Cloud Run e a chave diretamente dentro do plugin. Se deixar vazio, o plugin tenta usar o <code>wp-config.php</code>.</p>

      <form action="<?php echo $settings_action_url; ?>" method="post">
        <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::SAVE_SETTINGS_ACTION); ?>">
        <?php wp_nonce_field(FCRS_Plugin::SETTINGS_NONCE, 'fcrs_settings_nonce'); ?>

        <p>
          <label for="fcrs-endpoint"><strong>URL do Cloud Run</strong></label><br>
          <input
            id="fcrs-endpoint"
            type="url"
            name="fcrs_endpoint"
            value="<?php echo esc_attr((string) $settings_values['endpoint']); ?>"
            placeholder="https://seu-servico.a.run.app"
            style="width:100%; max-width:680px;"
          >
        </p>

        <p>
          <label for="fcrs-secret"><strong>Chave / senha do endpoint</strong></label><br>
          <input
            id="fcrs-secret"
            type="text"
            name="fcrs_secret"
            value="<?php echo esc_attr((string) $settings_values['secret']); ?>"
            placeholder="Cole aqui o valor do X-Site-Secret"
            style="width:100%; max-width:680px;"
            autocomplete="off"
          >
        </p>

        <p>
          <label for="fcrs-timeout"><strong>Timeout</strong></label><br>
          <input
            id="fcrs-timeout"
            type="number"
            min="30"
            step="1"
            name="fcrs_timeout"
            value="<?php echo esc_attr((string) $settings_values['timeout']); ?>"
            style="width:120px;"
          >
          <span>segundos</span>
        </p>

        <p>
          <label for="fcrs-model"><strong>Modelo fixo</strong> (opcional)</label><br>
          <input
            id="fcrs-model"
            type="text"
            name="fcrs_model"
            value="<?php echo esc_attr((string) $settings_values['model']); ?>"
            placeholder="Ex.: gemini-3-pro-image-preview"
            style="width:100%; max-width:400px;"
          >
        </p>

        <p>
          <button type="submit" class="button button-primary">Salvar configuracoes</button>
        </p>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($show_config_box) : ?>
    <div style="padding:12px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
      <p><strong>Endpoint em uso:</strong> <?php echo $config['endpoint'] ? esc_html($config['endpoint']) : 'Nao configurado'; ?></p>
      <p><strong>Chave configurada:</strong> <?php echo $config['secret'] ? 'Sim' : 'Nao'; ?></p>
      <p><strong>Modelo fixo:</strong> <?php echo $config['model'] ? esc_html($config['model']) : 'Nao definido'; ?></p>
      <p><strong>Timeout:</strong> <?php echo esc_html((string) $config['timeout']); ?> segundos</p>
      <p><strong>Origem da configuracao principal:</strong> <?php echo 'plugin' === $config['source'] ? 'Plugin' : 'wp-config.php'; ?></p>
    </div>
  <?php endif; ?>

  <?php if (!$config['ready']) : ?>
    <p>Defina a URL do Cloud Run e a chave acima para liberar o painel de teste.</p>
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
    <h3 style="margin-top:0;">Painel de teste</h3>
    <p>Os templates abaixo correspondem aos templates internos que existem no seu Cloud Run. O campo <strong>Prompt</strong> complementa esse template com a instrucao que voce quiser.</p>

    <div style="margin:12px 0 20px 0; padding:12px; background:#f6f7f7; border:1px solid #dcdcde;">
      <p style="margin-top:0;"><strong>O que cada template faz</strong></p>
      <ul style="margin-bottom:0;">
        <?php foreach ($templates as $template_key => $template_data) : ?>
          <li>
            <strong><?php echo esc_html($template_data['label']); ?></strong>:
            <?php echo esc_html($template_data['description']); ?>
            <code><?php echo esc_html($template_key); ?></code>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <form action="<?php echo $generate_action_url; ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::GENERATE_ACTION); ?>">
      <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
      <?php wp_nonce_field(FCRS_Plugin::NONCE, 'fcrs_nonce'); ?>

      <p>
        <label for="fcrs-template"><strong>Base criativa</strong></label><br>
        <select id="fcrs-template" name="template" required>
          <?php foreach ($templates as $template_key => $template_data) : ?>
            <option value="<?php echo esc_attr($template_key); ?>" <?php selected($selected_template, $template_key); ?>>
              <?php echo esc_html($template_data['label']); ?>
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
          cols="70"
          placeholder="Descreva a imagem que voce quer gerar. Ex.: fundo luxuoso, luz cinematografica, estilo editorial."
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
        <button type="submit" class="button button-primary" <?php disabled(!$config['ready']); ?>>
          <?php echo esc_html($submit_label); ?>
        </button>
      </p>
    </form>
  </div>
</div>
