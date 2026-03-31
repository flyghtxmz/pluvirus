<?php

defined('ABSPATH') || exit;
?>
<div class="fcrs-panel" style="max-width: 1380px; width: 100%;">
  <?php if (!empty($_GET['fcrs_template_saved'])) : ?>
    <div class="notice notice-success is-dismissible"><p>Template salvo.</p></div>
  <?php endif; ?>

  <?php if (!empty($_GET['fcrs_template_deleted'])) : ?>
    <div class="notice notice-success is-dismissible"><p>Template removido.</p></div>
  <?php endif; ?>

  <?php if (!empty($_GET['fcrs_template_error'])) : ?>
    <div class="notice notice-error is-dismissible">
      <p>
        <?php
        $template_error = sanitize_key(wp_unslash($_GET['fcrs_template_error']));

        switch ($template_error) {
          case 'missing_label':
            echo esc_html('Informe um nome para o template.');
            break;
          case 'missing_prompt':
            echo esc_html('Informe o prompt interno do template.');
            break;
          case 'missing_parameter_name':
            echo esc_html('Informe um nome valido para o parametro do template.');
            break;
          case 'template_in_use':
            echo esc_html('Esse template esta em uso em algum artigo e nao pode ser removido agora.');
            break;
          case 'missing_template':
            echo esc_html('Template invalido.');
            break;
          default:
            echo esc_html('Nao foi possivel salvar o template.');
            break;
        }
        ?>
      </p>
    </div>
  <?php endif; ?>

  <?php if (!empty($_GET['fcrs_preview_saved'])) : ?>
    <div class="notice notice-success is-dismissible"><p>Preview do artigo salvo.</p></div>
  <?php endif; ?>

  <?php if (!empty($_GET['fcrs_preview_error'])) : ?>
    <div class="notice notice-error is-dismissible">
      <p>
        <?php
        $preview_error = sanitize_key(wp_unslash($_GET['fcrs_preview_error']));

        switch ($preview_error) {
          case 'invalid_post':
            echo esc_html('Escolha um artigo ou pagina valido para editar o preview.');
            break;
          default:
            echo esc_html('Nao foi possivel salvar o preview do artigo.');
            break;
        }
        ?>
      </p>
    </div>
  <?php endif; ?>

  <h2 class="nav-tab-wrapper" style="margin:18px 0 16px;">
    <a href="<?php echo esc_url(admin_url('tools.php?page=' . FCRS_Plugin::PAGE_SLUG)); ?>" class="nav-tab<?php echo 'main' === $current_tab ? ' nav-tab-active' : ''; ?>">
      Painel principal
    </a>
    <a href="<?php echo esc_url(add_query_arg(array('page' => FCRS_Plugin::PAGE_SLUG, 'fcrs_tab' => 'article_preview', 'fcrs_preview_post_id' => $selected_preview_post_id), admin_url('tools.php'))); ?>" class="nav-tab<?php echo 'article_preview' === $current_tab ? ' nav-tab-active' : ''; ?>">
      Preview do artigo
    </a>
  </h2>

  <?php if ('main' === $current_tab) : ?>

  <style>
    .fcrs-prompt-editor {
      position: relative;
      width: 100%;
      border: 1px solid #8c8f94;
      border-radius: 4px;
      background: #fff;
      overflow: hidden;
    }

    .fcrs-prompt-editor:focus-within {
      border-color: #2271b1;
      box-shadow: 0 0 0 1px #2271b1;
    }

    .fcrs-prompt-editor__mirror,
    .fcrs-prompt-editor__textarea {
      width: 100%;
      min-height: 170px;
      margin: 0;
      padding: 10px 12px;
      box-sizing: border-box;
      font: 400 13px/1.5 Consolas, Monaco, monospace;
      letter-spacing: normal;
      white-space: pre-wrap;
      word-break: break-word;
      overflow-wrap: anywhere;
      tab-size: 2;
    }

    .fcrs-prompt-editor__mirror {
      color: #1d2327;
      pointer-events: none;
      overflow: hidden;
    }

    .fcrs-prompt-editor__textarea {
      position: absolute;
      inset: 0;
      border: 0 !important;
      background: transparent !important;
      color: transparent !important;
      caret-color: #1d2327;
      resize: vertical;
      box-shadow: none !important;
      overflow: auto !important;
    }

    .fcrs-prompt-editor__textarea:focus {
      box-shadow: none !important;
      outline: none !important;
    }

    .fcrs-prompt-editor__token {
      display: inline;
      border-radius: 999px;
      background: #1d4ed8;
      color: #ffffff;
      font-weight: 400;
      box-shadow: 0 0 0 2px #1d4ed8;
      -webkit-box-decoration-break: clone;
      box-decoration-break: clone;
    }
  </style>

  <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
    <h2 style="margin-top:0;">Configuracao de conexao</h2>
    <p>Salve aqui a URL do endpoint e a chave de acesso. Isso elimina a dependencia do <code>wp-config.php</code> para o uso normal do plugin.</p>

    <form action="<?php echo $settings_action_url; ?>" method="post">
      <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::SAVE_SETTINGS_ACTION); ?>">
      <?php wp_nonce_field(FCRS_Plugin::SETTINGS_NONCE, 'fcrs_settings_nonce'); ?>

      <p>
        <label for="fcrs-endpoint"><strong>URL do endpoint</strong></label><br>
        <input id="fcrs-endpoint" type="url" name="fcrs_endpoint" value="<?php echo esc_attr((string) $settings_values['endpoint']); ?>" placeholder="https://seu-servico.a.run.app" style="width:100%; max-width:760px;">
      </p>

      <p>
        <label for="fcrs-secret"><strong>Chave / senha do endpoint</strong></label><br>
        <input id="fcrs-secret" type="text" name="fcrs_secret" value="<?php echo esc_attr((string) $settings_values['secret']); ?>" placeholder="Valor do header X-Site-Secret" style="width:100%; max-width:760px;" autocomplete="off">
      </p>

      <p>
        <label for="fcrs-timeout"><strong>Timeout</strong></label><br>
        <input id="fcrs-timeout" type="number" min="30" step="1" name="fcrs_timeout" value="<?php echo esc_attr((string) $settings_values['timeout']); ?>" style="width:120px;">
        <span>segundos</span>
      </p>

      <p>
        <label for="fcrs-process-type"><strong>Tipo de processo</strong></label><br>
        <select id="fcrs-process-type" name="fcrs_process_type" style="width:320px;">
          <?php foreach ($process_type_options as $process_type_key => $process_type_label) : ?>
            <option value="<?php echo esc_attr($process_type_key); ?>" <?php selected((string) $settings_values['process_type'], $process_type_key); ?>>
              <?php echo esc_html($process_type_label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <button type="submit" class="button button-primary">Salvar configuracoes</button>
      </p>
    </form>
  </div>

  <div style="padding:12px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
    <p><strong>Endpoint em uso:</strong> <?php echo $config['endpoint'] ? esc_html($config['endpoint']) : 'Nao configurado'; ?></p>
    <p><strong>Chave configurada:</strong> <?php echo $config['secret'] ? 'Sim' : 'Nao'; ?></p>
    <p><strong>Timeout:</strong> <?php echo esc_html((string) $config['timeout']); ?> segundos</p>
    <p><strong>Tipo de processo atual:</strong> <?php echo esc_html($process_type_options[(string) $config['process_type']]); ?></p>
    <p><strong>Origem da configuracao principal:</strong> <?php echo 'plugin' === $config['source'] ? 'Plugin' : 'wp-config.php'; ?></p>
  </div>

  <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
    <h2 style="margin-top:0;">Como funciona por artigo</h2>
    <p>Crie os templates aqui na home do plugin. Depois, no editor do artigo, a caixa lateral <strong>Menezes Studio</strong> passa a listar esses templates para voce escolher qual artigo usa qual configuracao.</p>
  </div>

  <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
    <h2 style="margin-top:0;">Templates</h2>
    <p>Esta e a secao que faltava: os templates passam a ser cadastrados aqui. Cada template guarda o prompt puro e tambem as opcoes padrao de imagem, e depois fica disponivel no editor do artigo.</p>

    <div style="display:grid; grid-template-columns:minmax(0, 1.5fr) minmax(360px, 1fr); gap:18px; align-items:start;">
      <div style="overflow:auto;">
        <table class="widefat striped">
          <thead>
            <tr>
              <th>Nome</th>
              <th>Chave</th>
              <th>Modelo</th>
              <th>Imagem</th>
              <th>Uso</th>
              <th>Geradas</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($templates)) : ?>
              <?php foreach ($templates as $template_key => $template_data) : ?>
                <?php $usage_count = method_exists($this, 'count_template_usage') ? $this->count_template_usage($template_key) : 0; ?>
                <?php $generated_count = method_exists($this, 'get_template_generation_count') ? $this->get_template_generation_count($template_key) : 0; ?>
                <tr>
                  <td>
                    <strong><?php echo esc_html((string) $template_data['label']); ?></strong>
                    <?php if (!empty($template_data['description'])) : ?>
                      <div style="margin-top:4px; color:#50575e;"><?php echo esc_html((string) $template_data['description']); ?></div>
                    <?php endif; ?>
                  </td>
                  <td><code><?php echo esc_html($template_key); ?></code></td>
                  <td><?php echo esc_html((string) $models_by_type['image'][(string) $template_data['default_model']]); ?></td>
                  <td>
                    <?php if (!empty($template_data['image_options'])) : ?>
                      <div><?php echo esc_html((string) $template_data['image_options']['imageSize']); ?> / <?php echo esc_html(!empty($aspect_ratio_options[(string) $template_data['image_options']['aspectRatio']]) ? (string) $aspect_ratio_options[(string) $template_data['image_options']['aspectRatio']] : (string) $template_data['image_options']['aspectRatio']); ?></div>
                      <div><?php echo esc_html((string) $format_options[(string) $template_data['image_options']['outputMimeType']]); ?></div>
                      <div><?php echo !empty($template_data['require_reference']) ? 'Foto obrigatoria' : 'Foto opcional'; ?></div>
                      <div><?php echo !empty($template_data['use_template_reference_image']) ? 'Imagem fixa do template' : 'Sem imagem fixa'; ?></div>
                      <div>Ate <?php echo esc_html((string) $this->get_template_max_reference_images($template_data)); ?> fotos</div>
                      <?php if (!empty($template_data['use_text_parameter']) && !empty($template_data['text_parameter_name'])) : ?>
                        <div>Parametro <?php echo esc_html('{' . (string) $template_data['text_parameter_name'] . '}'); ?></div>
                        <div><?php echo esc_html((string) $template_data['text_parameter_word_limit']); ?> palavras / <?php echo esc_html((string) $template_data['text_parameter_letter_limit']); ?> letras</div>
                      <?php endif; ?>
                      <?php if (!empty($template_data['template_color']) && !empty($template_color_options[(string) $template_data['template_color']])) : ?>
                        <div>Cor <?php echo esc_html((string) $template_color_options[(string) $template_data['template_color']]['label']); ?></div>
                      <?php endif; ?>
                      <div><?php echo !empty($template_data['use_rewarded']) ? 'Rewarded ativado' : 'Rewarded desligado'; ?></div>
                      <div><?php echo esc_html($template_languages[(string) $template_data['ui_language']]); ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?php echo esc_html((string) $usage_count); ?></td>
                  <td><?php echo esc_html((string) $generated_count); ?></td>
                  <td>
                    <a class="button button-secondary" href="<?php echo esc_url(add_query_arg(array('page' => FCRS_Plugin::PAGE_SLUG, 'fcrs_edit_template' => $template_key), admin_url('tools.php'))); ?>">
                      Editar
                    </a>
                    <form action="<?php echo $template_action_url; ?>" method="post" style="display:inline;">
                      <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::DELETE_TEMPLATE_ACTION); ?>">
                      <input type="hidden" name="fcrs_template_key" value="<?php echo esc_attr($template_key); ?>">
                      <?php wp_nonce_field(FCRS_Plugin::DELETE_TEMPLATE_NONCE, 'fcrs_delete_template_nonce'); ?>
                      <button type="submit" class="button" <?php disabled($usage_count > 0); ?> onclick="return window.confirm('Remover este template?');">
                        Excluir
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else : ?>
              <tr>
                <td colspan="7">Nenhum template cadastrado ainda.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div style="padding:16px; background:#f6f7f7; border:1px solid #dcdcde;">
        <h3 style="margin-top:0;"><?php echo $editing_template ? 'Editar template' : 'Criar template'; ?></h3>
        <form action="<?php echo $template_action_url; ?>" method="post">
          <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::SAVE_TEMPLATE_ACTION); ?>">
          <input type="hidden" name="fcrs_template_original_key" value="<?php echo esc_attr((string) $template_form_values['original_key']); ?>">
          <?php wp_nonce_field(FCRS_Plugin::TEMPLATE_NONCE, 'fcrs_template_nonce'); ?>

          <p>
            <label for="fcrs-template-label"><strong>Nome do template</strong></label><br>
            <input id="fcrs-template-label" type="text" name="fcrs_template_label" value="<?php echo esc_attr((string) $template_form_values['label']); ?>" style="width:100%;" required>
          </p>

          <p>
            <label for="fcrs-template-key-admin"><strong>Chave</strong> (opcional)</label><br>
            <input id="fcrs-template-key-admin" type="text" name="fcrs_template_key" value="<?php echo esc_attr((string) $template_form_values['key']); ?>" placeholder="ex.: capa_artigo_luxo" style="width:100%;" <?php echo $editing_template ? 'readonly' : ''; ?>>
            <?php if ($editing_template) : ?>
              <span style="display:block; margin-top:4px; color:#50575e;">A chave fica travada depois que o template existe, para nao quebrar artigos ja vinculados.</span>
            <?php endif; ?>
          </p>

          <p>
            <label for="fcrs-template-description"><strong>Descricao</strong></label><br>
            <textarea id="fcrs-template-description" name="fcrs_template_description" rows="3" style="width:100%;"><?php echo esc_textarea((string) $template_form_values['description']); ?></textarea>
          </p>

          <p>
            <label for="fcrs-locked-prompt"><strong>Prompt</strong></label><br>
            <div class="fcrs-prompt-editor" data-fcrs-prompt-editor>
              <div class="fcrs-prompt-editor__mirror" data-fcrs-prompt-mirror aria-hidden="true"></div>
              <textarea id="fcrs-locked-prompt" name="fcrs_locked_prompt" rows="7" class="fcrs-prompt-editor__textarea" data-fcrs-prompt-input required><?php echo esc_textarea((string) $template_form_values['locked_prompt']); ?></textarea>
            </div>
            <span style="display:block; margin-top:4px; color:#50575e;">Marcadores como <code>{nome}</code> aparecem destacados em azul para facilitar a montagem do prompt.</span>
          </p>

          <p>
            <label for="fcrs-template-ui-language"><strong>Idioma da interface do artigo</strong></label><br>
            <select id="fcrs-template-ui-language" name="fcrs_template_ui_language" style="width:100%;">
              <?php foreach ($template_languages as $language_key => $language_label) : ?>
                <option value="<?php echo esc_attr($language_key); ?>" <?php selected((string) $template_form_values['ui_language'], $language_key); ?>>
                  <?php echo esc_html($language_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span style="display:block; margin-top:4px; color:#50575e;">Esse dropdown traduz os textos do gerador no artigo: dicas, loading, upload, download e demais mensagens visuais.</span>
          </p>

          <p>
            <label for="fcrs-template-color"><strong>Cor do artigo</strong></label><br>
            <span style="display:flex; align-items:center; gap:10px;">
              <select id="fcrs-template-color" name="fcrs_template_color" style="flex:1;">
                <?php foreach ($template_color_options as $color_key => $color_data) : ?>
                  <option
                    value="<?php echo esc_attr($color_key); ?>"
                    data-accent="<?php echo esc_attr((string) $color_data['accent']); ?>"
                    data-soft="<?php echo esc_attr((string) $color_data['soft_strong']); ?>"
                    <?php selected((string) $template_form_values['template_color'], $color_key); ?>
                  >
                    <?php echo esc_html((string) $color_data['label']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span id="fcrs-template-color-preview" style="display:inline-flex; align-items:center; gap:6px;">
                <span id="fcrs-template-color-preview-main" style="display:inline-block; width:18px; height:18px; border-radius:999px; background:#2563eb; border:1px solid rgba(15,23,42,0.12);"></span>
                <span id="fcrs-template-color-preview-soft" style="display:inline-block; width:18px; height:18px; border-radius:999px; background:#dbeafe; border:1px solid rgba(15,23,42,0.12);"></span>
              </span>
            </span>
            <span style="display:block; margin-top:4px; color:#50575e;">Essa cor vai para o visual do artigo, mantendo o mesmo efeito de tom principal com uma versao mais clara.</span>
          </p>

          <p>
            <label>
              <input type="checkbox" name="fcrs_template_require_reference" value="1" <?php checked((string) $template_form_values['require_reference'], '1'); ?>>
              <strong>A foto e obrigatoria?</strong>
            </label>
            <span style="display:block; margin-top:4px; color:#50575e;">Vem ligado por padrao. Quando ativo, o visitante precisa enviar pelo menos uma foto.</span>
          </p>

          <p>
            <label>
              <input type="checkbox" id="fcrs-template-use-text-parameter" name="fcrs_template_use_text_parameter" value="1" <?php checked((string) $template_form_values['use_text_parameter'], '1'); ?>>
              <strong>Definir Parametro?</strong>
            </label>
            <span style="display:block; margin-top:4px; color:#50575e;">Quando ativo, o artigo deixa de pedir a foto do usuario e mostra apenas uma entrada de texto ligada ao prompt.</span>
          </p>

          <div id="fcrs-template-text-parameter-fields" style="<?php echo '1' === (string) $template_form_values['use_text_parameter'] ? '' : 'display:none; '; ?>margin:16px 0; padding:12px; border:1px solid #dcdcde; background:#fff;">
            <p style="margin-top:0;">
              <label for="fcrs-template-text-parameter-name"><strong>Nome do parametro</strong></label><br>
              <input id="fcrs-template-text-parameter-name" type="text" name="fcrs_template_text_parameter_name" value="<?php echo esc_attr((string) $template_form_values['text_parameter_name']); ?>" placeholder="nome" style="width:100%;">
              <span style="display:block; margin-top:4px; color:#50575e;">Use letras, numeros e underline. O marcador no prompt ficara como <code id="fcrs-template-parameter-placeholder-preview"><?php echo esc_html('{' . ((string) $template_form_values['text_parameter_name'] ? (string) $template_form_values['text_parameter_name'] : 'nome') . '}'); ?></code>.</span>
            </p>

            <p>
              <label for="fcrs-template-text-parameter-word-limit"><strong>Limite de palavras</strong></label><br>
              <input id="fcrs-template-text-parameter-word-limit" type="number" min="1" max="50" step="1" name="fcrs_template_text_parameter_word_limit" value="<?php echo esc_attr((string) $template_form_values['text_parameter_word_limit']); ?>" style="width:120px;">
            </p>

            <p style="margin-bottom:0;">
              <label for="fcrs-template-text-parameter-letter-limit"><strong>Limite de letras por palavra</strong></label><br>
              <input id="fcrs-template-text-parameter-letter-limit" type="number" min="1" max="100" step="1" name="fcrs_template_text_parameter_letter_limit" value="<?php echo esc_attr((string) $template_form_values['text_parameter_letter_limit']); ?>" style="width:120px;">
            </p>
          </div>

          <p>
            <label>
              <input type="checkbox" id="fcrs-template-use-reference-image" name="fcrs_template_use_reference_image" value="1" <?php checked((string) $template_form_values['use_template_reference_image'], '1'); ?>>
              <strong>Usar imagem de referencia do template?</strong>
            </label>
            <span style="display:block; margin-top:4px; color:#50575e;">Quando ativo, voce escolhe uma imagem da galeria e ela passa a ser enviada automaticamente junto com esse template.</span>
          </p>

          <div id="fcrs-template-reference-image-fields" style="<?php echo '1' === (string) $template_form_values['use_template_reference_image'] ? '' : 'display:none; '; ?>margin:16px 0; padding:12px; border:1px solid #dcdcde; background:#fff;">
            <input type="hidden" id="fcrs-template-reference-image-id" name="fcrs_template_reference_image_id" value="<?php echo esc_attr((string) $template_form_values['template_reference_image_id']); ?>">
            <input type="hidden" id="fcrs-template-reference-image-url" name="fcrs_template_reference_image_url" value="<?php echo esc_attr((string) $template_form_values['template_reference_image_url']); ?>">

            <p style="margin-top:0;">
              <strong>Imagem de referencia fixa</strong><br>
              <span style="display:block; margin-top:4px; color:#50575e;">Selecione uma imagem da biblioteca para o template usar como referencia interna. No artigo, o upload deixa de ser pedido quando esta imagem estiver ativa.</span>
            </p>

            <p style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
              <button type="button" class="button" id="fcrs-select-template-reference-image">Escolher imagem</button>
              <button type="button" class="button" id="fcrs-remove-template-reference-image">Remover imagem</button>
            </p>

            <div id="fcrs-template-reference-image-frame" style="<?php echo !empty($template_form_values['template_reference_image_url']) ? '' : 'display:none; '; ?>margin:0;">
              <img id="fcrs-template-reference-image-display" src="<?php echo esc_url((string) $template_form_values['template_reference_image_url']); ?>" alt="Imagem de referencia do template" style="display:block; max-width:100%; width:min(420px, 100%); border-radius:12px; border:1px solid #dcdcde; background:#f6f7f7;">
            </div>
          </div>

          <p>
            <label for="fcrs-template-max-reference-images"><strong>Quantidade maxima de fotos</strong></label><br>
            <input id="fcrs-template-max-reference-images" type="number" min="1" max="<?php echo esc_attr((string) FCRS_Plugin::MAX_REFERENCE_IMAGES); ?>" step="1" name="fcrs_template_max_reference_images" value="<?php echo esc_attr((string) $template_form_values['max_reference_images']); ?>" style="width:120px;">
            <span style="display:block; margin-top:4px; color:#50575e;">Esse limite aparece no artigo e tambem e validado no envio. Maximo tecnico atual: <?php echo esc_html((string) FCRS_Plugin::MAX_REFERENCE_IMAGES); ?> fotos.</span>
          </p>

          <p>
            <label>
              <input type="checkbox" name="fcrs_template_use_rewarded" value="1" <?php checked((string) $template_form_values['use_rewarded'], '1'); ?>>
              <strong>Utilizar rewarded?</strong>
            </label>
            <span style="display:block; margin-top:4px; color:#50575e;">Vem ligado por padrao. Quando ativo, o clique em baixar imagem chama o rewarded antes de liberar o download.</span>
          </p>

          <p>
            <label for="fcrs-default-model"><strong>Modelo padrao</strong></label><br>
            <select id="fcrs-default-model" name="fcrs_default_model" style="width:100%;" data-fcrs-image-model-select>
              <?php foreach ($models_by_type['image'] as $model_key => $model_label) : ?>
                <option value="<?php echo esc_attr($model_key); ?>" <?php selected($template_form_values['default_model'], $model_key); ?>>
                  <?php echo esc_html($model_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </p>

          <p>
            <label for="fcrs-template-image-size"><strong>Tamanho da imagem</strong></label><br>
            <select id="fcrs-template-image-size" name="fcrs_template_image_size" style="width:100%;" data-fcrs-image-size-select>
              <?php foreach ($image_size_options as $size_key => $size_label) : ?>
                <option value="<?php echo esc_attr($size_key); ?>" <?php selected($template_form_values['image_size'], $size_key); ?>>
                  <?php echo esc_html($size_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span style="display:block; margin-top:4px; color:#50575e;">Modelos suportados aceitam combinacoes diferentes. Exemplo: 4K depende do modelo.</span>
          </p>

          <p>
            <label for="fcrs-template-aspect-ratio"><strong>Proporcao</strong></label><br>
            <select id="fcrs-template-aspect-ratio" name="fcrs_template_aspect_ratio" style="width:100%;">
              <?php foreach ($aspect_ratio_options as $ratio_key => $ratio_label) : ?>
                <option value="<?php echo esc_attr($ratio_key); ?>" <?php selected($template_form_values['aspect_ratio'], $ratio_key); ?>>
                  <?php echo esc_html($ratio_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span style="display:block; margin-top:4px; color:#50575e;">Se escolher a opcao da imagem enviada, o plugin usa automaticamente a proporcao da primeira foto enviada na hora da geracao.</span>
          </p>

          <p>
            <label for="fcrs-template-output-format"><strong>Formato</strong></label><br>
            <select id="fcrs-template-output-format" name="fcrs_template_output_mime_type" style="width:100%;" data-fcrs-output-format-select>
              <?php foreach ($format_options as $format_key => $format_label) : ?>
                <option value="<?php echo esc_attr($format_key); ?>" <?php selected($template_form_values['output_mime_type'], $format_key); ?>>
                  <?php echo esc_html($format_label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </p>

          <p data-fcrs-jpeg-quality-wrapper>
            <label for="fcrs-template-compression-quality"><strong>Qualidade JPEG</strong></label><br>
            <input id="fcrs-template-compression-quality" type="number" min="0" max="100" step="1" name="fcrs_template_compression_quality" value="<?php echo esc_attr((string) $template_form_values['compression_quality']); ?>" style="width:120px;">
            <span style="margin-left:8px; color:#50575e;">0 a 100</span>
          </p>

          <p style="margin-bottom:0;">
            <button type="submit" class="button button-primary">
              <?php echo $editing_template ? 'Atualizar template' : 'Criar template'; ?>
            </button>
            <?php if ($editing_template) : ?>
              <a class="button" href="<?php echo esc_url(admin_url('tools.php?page=' . FCRS_Plugin::PAGE_SLUG)); ?>">Novo template</a>
            <?php endif; ?>
          </p>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var useReferenceCheckbox = document.getElementById('fcrs-template-use-reference-image');
      var referenceFields = document.getElementById('fcrs-template-reference-image-fields');
      var selectButton = document.getElementById('fcrs-select-template-reference-image');
      var removeButton = document.getElementById('fcrs-remove-template-reference-image');
      var imageIdInput = document.getElementById('fcrs-template-reference-image-id');
      var imageUrlInput = document.getElementById('fcrs-template-reference-image-url');
      var imageFrame = document.getElementById('fcrs-template-reference-image-frame');
      var imageDisplay = document.getElementById('fcrs-template-reference-image-display');
      var useTextParameterCheckbox = document.getElementById('fcrs-template-use-text-parameter');
      var textParameterFields = document.getElementById('fcrs-template-text-parameter-fields');
      var textParameterNameInput = document.getElementById('fcrs-template-text-parameter-name');
      var parameterPlaceholderPreview = document.getElementById('fcrs-template-parameter-placeholder-preview');
      var colorSelect = document.getElementById('fcrs-template-color');
      var colorPreviewMain = document.getElementById('fcrs-template-color-preview-main');
      var colorPreviewSoft = document.getElementById('fcrs-template-color-preview-soft');
      var promptInput = document.querySelector('[data-fcrs-prompt-input]');
      var promptMirror = document.querySelector('[data-fcrs-prompt-mirror]');
      var mediaFrame;

      function normalizeParameterNamePreview(value) {
        return String(value || '')
          .toLowerCase()
          .replace(/[^a-z0-9_]+/g, '_')
          .replace(/^_+|_+$/g, '') || 'nome';
      }

      function escapePromptHtml(text) {
        return String(text || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;');
      }

      function renderPromptHighlight() {
        if (!promptInput || !promptMirror) {
          return;
        }

        var highlighted = escapePromptHtml(promptInput.value).replace(/\{[^{}\r\n]+\}/g, function (match) {
          return '<span class="fcrs-prompt-editor__token">' + match + '</span>';
        });

        promptMirror.innerHTML = highlighted + '\n';
        promptMirror.scrollTop = promptInput.scrollTop;
        promptMirror.scrollLeft = promptInput.scrollLeft;
      }

      function toggleReferenceFields() {
        if (!useReferenceCheckbox || !referenceFields) {
          return;
        }

        referenceFields.style.display = useReferenceCheckbox.checked ? 'block' : 'none';
      }

      function toggleTextParameterFields() {
        if (!useTextParameterCheckbox || !textParameterFields) {
          return;
        }

        textParameterFields.style.display = useTextParameterCheckbox.checked ? 'block' : 'none';
      }

      function updateParameterPlaceholderPreview() {
        if (!parameterPlaceholderPreview) {
          return;
        }

        parameterPlaceholderPreview.textContent = '{' + normalizeParameterNamePreview(textParameterNameInput ? textParameterNameInput.value : '') + '}';
      }

      function updateTemplateColorPreview() {
        if (!colorSelect || !colorPreviewMain || !colorPreviewSoft) {
          return;
        }

        var selectedOption = colorSelect.selectedOptions.length ? colorSelect.selectedOptions[0] : null;

        if (!selectedOption) {
          return;
        }

        colorPreviewMain.style.background = selectedOption.getAttribute('data-accent') || '#2563eb';
        colorPreviewSoft.style.background = selectedOption.getAttribute('data-soft') || '#dbeafe';
      }

      function updateTemplateReferencePreview(url, id) {
        if (imageIdInput) {
          imageIdInput.value = id || '';
        }

        if (imageUrlInput) {
          imageUrlInput.value = url || '';
        }

        if (!imageFrame || !imageDisplay) {
          return;
        }

        if (url) {
          imageDisplay.src = url;
          imageFrame.style.display = 'block';
          return;
        }

        imageDisplay.removeAttribute('src');
        imageFrame.style.display = 'none';
      }

      if (useReferenceCheckbox) {
        useReferenceCheckbox.addEventListener('change', function () {
          toggleReferenceFields();

          if (!useReferenceCheckbox.checked) {
            updateTemplateReferencePreview('', '');
          }
        });

        toggleReferenceFields();
      }

      if (useTextParameterCheckbox) {
        useTextParameterCheckbox.addEventListener('change', toggleTextParameterFields);
        toggleTextParameterFields();
      }

      if (textParameterNameInput) {
        textParameterNameInput.addEventListener('input', updateParameterPlaceholderPreview);
        updateParameterPlaceholderPreview();
      }

      if (colorSelect) {
        colorSelect.addEventListener('change', updateTemplateColorPreview);
        updateTemplateColorPreview();
      }

      if (selectButton) {
        selectButton.addEventListener('click', function () {
          if (typeof wp === 'undefined' || !wp.media) {
            return;
          }

          if (mediaFrame) {
            mediaFrame.open();
            return;
          }

          mediaFrame = wp.media({
            title: 'Escolher imagem de referencia do template',
            button: { text: 'Usar esta imagem' },
            multiple: false
          });

          mediaFrame.on('select', function () {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            updateTemplateReferencePreview(attachment.url || '', attachment.id || '');

            if (useReferenceCheckbox) {
              useReferenceCheckbox.checked = true;
              toggleReferenceFields();
            }
          });

          mediaFrame.open();
        });
      }

      if (removeButton) {
        removeButton.addEventListener('click', function () {
          updateTemplateReferencePreview('', '');
        });
      }

      if (promptInput && promptMirror) {
        promptInput.addEventListener('input', renderPromptHighlight);
        promptInput.addEventListener('scroll', renderPromptHighlight);
        renderPromptHighlight();
      }
    });
  </script>

  <?php if (!empty($result['message'])) : ?>
    <div style="padding:12px; border:1px solid #dcdcde; background:#fff; margin:16px 0;" data-fcrs-blocking-ui="0" <?php if (!empty($result['job_id']) && empty($result['preview_url']) && in_array((string) $result['status'], array('queued', 'processing'), true)) : ?>data-fcrs-job-monitor data-job-id="<?php echo esc_attr((string) $result['job_id']); ?>" data-result-token="<?php echo esc_attr((string) $result_token); ?>"<?php endif; ?>>
      <p><strong>Status:</strong> <span data-fcrs-job-status-text><?php echo esc_html((string) $result['status']); ?></span></p>
      <p data-fcrs-job-message><?php echo esc_html((string) $result['message']); ?></p>
      <?php $parameter_rows = $this->get_result_parameters($result); ?>
      <?php if (!empty($parameter_rows)) : ?>
        <div style="margin:12px 0;">
          <p style="margin-bottom:8px;"><strong>Parametros utilizados</strong></p>
          <?php foreach ($parameter_rows as $parameter_label => $parameter_value) : ?>
            <p style="margin:4px 0;"><strong><?php echo esc_html($parameter_label); ?>:</strong> <?php echo esc_html($parameter_value); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($result['text'])) : ?>
        <p><?php echo esc_html((string) $result['text']); ?></p>
      <?php endif; ?>
      <?php if (!empty($result['temporary_result']) && empty($result['attachment_id'])) : ?>
        <p>Resultado temporario no hub<?php if (!empty($result['result_url_expires_at'])) : ?> ate <?php echo esc_html((string) $result['result_url_expires_at']); ?><?php endif; ?>.</p>
      <?php endif; ?>
      <?php if (!empty($result['attachment_id'])) : ?>
        <p>Biblioteca de midia: ID <?php echo esc_html((string) $result['attachment_id']); ?>.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php $admin_download_filename = !empty($result['download_filename']) ? (string) $result['download_filename'] : 'imagem-gerada'; ?>
  <div class="fcrs-result-card<?php echo !empty($result['preview_url']) ? '' : ' is-hidden'; ?>" data-fcrs-result-card style="margin:16px 0;">
    <div class="fcrs-result-card__media" data-fcrs-protected-result oncontextmenu="return false;">
      <div
        data-fcrs-result-image
        class="fcrs-result-card__image<?php echo !empty($result['preview_url']) ? ' has-image' : ''; ?>"
        role="img"
        aria-label="Preview da imagem gerada"
        style="<?php echo !empty($result['preview_url']) ? esc_attr('background-image:url(' . esc_url_raw((string) $result['preview_url']) . ');') : ''; ?>"
      ></div>
      <div class="fcrs-result-card__shield" aria-hidden="true"></div>
    </div>
    <div class="fcrs-result-card__actions">
      <a
        class="fcrs-download-button<?php echo !empty($admin_download_url) ? '' : ' is-hidden'; ?>"
        data-fcrs-download-link
        href="<?php echo esc_url(!empty($admin_download_url) ? $admin_download_url : '#'); ?>"
        data-url="<?php echo esc_url($admin_download_url); ?>"
        data-download-url="<?php echo esc_url($admin_download_url); ?>"
        data-download-filename="<?php echo esc_attr($admin_download_filename); ?>"
        data-fcrs-rewarded-download="0"
        download
      >
        Baixar imagem
      </a>
    </div>
  </div>

  <div style="padding:16px; border:1px solid #dcdcde; background:#fff;">
    <h2 style="margin-top:0;">Painel de teste</h2>
    <p>O teste segue a mesma logica do site: primeiro voce escolhe o tipo de geracao, depois o modelo. O template e opcional, e voce tambem pode escrever um prompt livre para testar sem template.</p>

    <div style="margin:12px 0 20px 0; padding:12px; background:#f6f7f7; border:1px solid #dcdcde;">
      <p style="margin-top:0;"><strong>Templates de imagem</strong></p>
      <?php if (!empty($image_templates)) : ?>
        <ul style="margin-bottom:0;">
          <?php foreach ($image_templates as $template_key => $template_data) : ?>
            <li>
              <strong><?php echo esc_html((string) $template_data['label']); ?></strong>:
              <?php echo esc_html((string) $template_data['description']); ?>
              <code><?php echo esc_html($template_key); ?></code>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else : ?>
        <p style="margin-bottom:0;">Voce ainda nao criou templates. Mesmo assim, ja pode testar usando <strong>Nenhum</strong> e preenchendo o prompt manualmente.</p>
      <?php endif; ?>
    </div>

    <form action="<?php echo $generate_action_url; ?>" method="post" enctype="multipart/form-data" class="fcrs-dynamic-form" data-fcrs-blocking-ui="0">
      <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::GENERATE_ACTION); ?>">
      <input type="hidden" name="redirect_to" value="<?php echo esc_attr(admin_url('tools.php?page=' . FCRS_Plugin::PAGE_SLUG)); ?>">
      <?php wp_nonce_field(FCRS_Plugin::NONCE, 'fcrs_nonce'); ?>

      <p>
        <label for="fcrs-generation-type"><strong>O que vai ser gerado?</strong></label><br>
        <select id="fcrs-generation-type" name="generationType" data-fcrs-generation-type>
          <option value="image" <?php selected($selected_generation_type, 'image'); ?>>Imagem</option>
          <option value="video" <?php selected($selected_generation_type, 'video'); ?>>Video</option>
        </select>
      </p>

      <p>
        <label for="fcrs-model"><strong>Modelo</strong></label><br>
        <select id="fcrs-model" name="model" data-fcrs-model-select data-fcrs-image-model-select>
          <?php foreach ($models_by_type as $type_key => $models) : ?>
            <?php foreach ($models as $model_key => $model_label) : ?>
              <option value="<?php echo esc_attr($model_key); ?>" data-generation-type="<?php echo esc_attr($type_key); ?>" <?php selected($selected_model, $model_key); ?>>
                <?php echo esc_html($model_label); ?>
              </option>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </select>
      </p>

      <p data-fcrs-template-wrapper>
        <label for="fcrs-template"><strong>Template</strong></label><br>
        <select id="fcrs-template" name="templateKey" data-fcrs-template-select>
          <option value="" data-generation-type="image" <?php selected($selected_template_key, ''); ?>>Nenhum</option>
          <?php foreach ($templates as $template_key => $template_data) : ?>
            <?php $template_parameter_name = !empty($template_data['text_parameter_name']) ? (string) $template_data['text_parameter_name'] : ''; ?>
            <option
              value="<?php echo esc_attr($template_key); ?>"
              data-generation-type="<?php echo esc_attr((string) $template_data['type']); ?>"
              data-uses-text-parameter="<?php echo !empty($template_data['use_text_parameter']) && $template_parameter_name ? '1' : '0'; ?>"
              data-parameter-placeholder="<?php echo esc_attr($template_parameter_name ? '{' . $template_parameter_name . '}' : ''); ?>"
              data-word-limit="<?php echo esc_attr(!empty($template_data['text_parameter_word_limit']) ? (string) $template_data['text_parameter_word_limit'] : ''); ?>"
              data-letter-limit="<?php echo esc_attr(!empty($template_data['text_parameter_letter_limit']) ? (string) $template_data['text_parameter_letter_limit'] : ''); ?>"
              <?php selected($selected_template_key, $template_key); ?>
            >
              <?php echo esc_html((string) $template_data['label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p data-fcrs-template-parameter-wrapper style="display:none;">
        <label for="fcrs-template-parameter-value"><strong data-fcrs-template-parameter-label>Parametro</strong></label><br>
        <input
          id="fcrs-template-parameter-value"
          type="text"
          name="templateParameterValue"
          value="<?php echo esc_attr((string) $selected_parameter_value); ?>"
          style="width:100%;"
          data-fcrs-text-parameter-input
          data-parameter-label=""
          data-parameter-placeholder=""
          data-word-limit=""
          data-letter-limit=""
          data-required-message="Preencha o campo %label%."
          data-word-limit-message="O campo %label% aceita no maximo %limit% palavras."
          data-letter-limit-message="Cada palavra do campo %label% pode ter no maximo %limit% letras."
        >
      </p>

      <p>
        <label for="fcrs-manual-prompt"><strong>Prompt</strong> (opcional)</label><br>
        <textarea id="fcrs-manual-prompt" name="manualPrompt" rows="6" style="width:100%;"><?php echo esc_textarea((string) $selected_prompt); ?></textarea>
        <span style="display:block; margin-top:4px; color:#50575e;">Se escolher <strong>Nenhum</strong>, este prompt sera enviado puro. Se escolher um template, este prompt sera somado ao prompt do template.</span>
      </p>

      <p>
        <label for="fcrs-test-image-size"><strong>Tamanho da imagem</strong></label><br>
        <select id="fcrs-test-image-size" name="fcrs_test_image_size" data-fcrs-image-size-select>
          <?php foreach ($image_size_options as $size_key => $size_label) : ?>
            <option value="<?php echo esc_attr($size_key); ?>" <?php selected($selected_image_options['imageSize'], $size_key); ?>>
              <?php echo esc_html($size_label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <label for="fcrs-test-aspect-ratio"><strong>Proporcao</strong></label><br>
        <select id="fcrs-test-aspect-ratio" name="fcrs_test_aspect_ratio">
          <?php foreach ($aspect_ratio_options as $ratio_key => $ratio_label) : ?>
            <option value="<?php echo esc_attr($ratio_key); ?>" <?php selected($selected_image_options['aspectRatio'], $ratio_key); ?>>
              <?php echo esc_html($ratio_label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p>
        <label for="fcrs-test-output-format"><strong>Formato</strong></label><br>
        <select id="fcrs-test-output-format" name="fcrs_test_output_mime_type" data-fcrs-output-format-select>
          <?php foreach ($format_options as $format_key => $format_label) : ?>
            <option value="<?php echo esc_attr($format_key); ?>" <?php selected($selected_image_options['outputMimeType'], $format_key); ?>>
              <?php echo esc_html($format_label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </p>

      <p data-fcrs-jpeg-quality-wrapper>
        <label for="fcrs-test-compression-quality"><strong>Qualidade JPEG</strong></label><br>
        <input id="fcrs-test-compression-quality" type="number" min="0" max="100" step="1" name="fcrs_test_compression_quality" value="<?php echo esc_attr((string) $selected_image_options['compressionQuality']); ?>" style="width:120px;">
        <span style="margin-left:8px; color:#50575e;">0 a 100</span>
      </p>

      <p data-fcrs-video-note style="display:none; color:#b32d2e;">
        A interface de video ja foi preparada no plugin, mas a API atual ainda so gera imagem.
      </p>

      <p>
        <label for="fcrs-reference-image"><strong>Imagem de referencia</strong> (opcional)</label><br>
        <input id="fcrs-reference-image" type="file" name="referenceImage[]" accept="image/*" multiple>
        <span style="display:block; margin-top:4px; color:#50575e;">Voce pode testar com ate <?php echo esc_html((string) FCRS_Plugin::MAX_REFERENCE_IMAGES); ?> imagens. No artigo, cada template pode limitar uma quantidade menor.</span>
      </p>

      <div data-fcrs-preview style="display:none; margin:12px 0;">
        <div><strong>Preview das imagens de referencia:</strong></div>
        <div class="fcrs-preview-strip__grid" data-fcrs-preview-grid style="margin-top:10px;"></div>
      </div>

      <p>
        <label>
          <input type="checkbox" name="saveToMedia" value="1" <?php checked($save_to_media); ?>>
          Salvar tambem na biblioteca de midia
        </label>
      </p>

      <p>
        <button type="submit" class="button button-primary" data-fcrs-submit <?php disabled(!$config['ready']); ?>>
          Testar endpoint e gerar
        </button>
      </p>
    </form>
  </div>
  <?php else : ?>
    <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
      <h2 style="margin-top:0;">Preview do artigo</h2>
      <p>Escolha um artigo ou pagina para definir a imagem, o titulo e o subtitulo que voce quer usar no preview do link.</p>

      <form action="<?php echo esc_url(admin_url('tools.php')); ?>" method="get" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap; margin-top:16px;">
        <input type="hidden" name="page" value="<?php echo esc_attr(FCRS_Plugin::PAGE_SLUG); ?>">
        <input type="hidden" name="fcrs_tab" value="article_preview">
        <div style="min-width:320px; flex:1;">
          <label for="fcrs-preview-post-id"><strong>Artigo ou pagina</strong></label><br>
          <select id="fcrs-preview-post-id" name="fcrs_preview_post_id" style="width:100%;">
            <?php foreach ($preview_target_posts as $preview_post_option) : ?>
              <option value="<?php echo esc_attr((string) $preview_post_option->ID); ?>" <?php selected((int) $selected_preview_post_id, (int) $preview_post_option->ID); ?>>
                <?php echo esc_html($preview_post_option->post_title ? $preview_post_option->post_title : '(Sem titulo)'); ?> - <?php echo esc_html(ucfirst((string) $preview_post_option->post_type)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <button type="submit" class="button">Carregar</button>
        </div>
      </form>
    </div>

    <?php if ($selected_preview_post) : ?>
      <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
        <p style="margin-top:0;"><strong>Selecionado:</strong> <?php echo esc_html(get_the_title($selected_preview_post)); ?></p>
        <p>
          <a class="button button-secondary" href="<?php echo esc_url(get_permalink($selected_preview_post)); ?>" target="_blank" rel="noopener noreferrer">Ver artigo</a>
          <a class="button" href="<?php echo esc_url(get_edit_post_link($selected_preview_post->ID)); ?>">Editar no WordPress</a>
        </p>

        <form action="<?php echo $article_preview_action_url; ?>" method="post">
          <input type="hidden" name="action" value="<?php echo esc_attr(FCRS_Plugin::SAVE_ARTICLE_PREVIEW_ACTION); ?>">
          <input type="hidden" name="fcrs_preview_post_id" value="<?php echo esc_attr((string) $selected_preview_post->ID); ?>">
          <?php wp_nonce_field(FCRS_Plugin::ARTICLE_PREVIEW_NONCE, 'fcrs_article_preview_nonce'); ?>

          <p>
            <label for="fcrs-preview-custom-title"><strong>Titulo do preview</strong></label><br>
            <input id="fcrs-preview-custom-title" type="text" name="fcrs_preview_title" value="<?php echo esc_attr((string) $selected_preview_values['title']); ?>" style="width:100%;" placeholder="Ex.: Restaure suas fotos antigas com IA">
          </p>

          <p>
            <label for="fcrs-preview-custom-subtitle"><strong>Subtitulo do preview</strong></label><br>
            <textarea id="fcrs-preview-custom-subtitle" name="fcrs_preview_subtitle" rows="4" style="width:100%;" placeholder="Texto curto que aparecera no preview do link."><?php echo esc_textarea((string) $selected_preview_values['subtitle']); ?></textarea>
          </p>

          <p>
            <label><strong>Imagem do preview</strong></label><br>
            <input type="hidden" id="fcrs-preview-image-id" name="fcrs_preview_image_id" value="<?php echo esc_attr((string) $selected_preview_values['image_id']); ?>">
            <input type="text" id="fcrs-preview-image-url" name="fcrs_preview_image_url" value="<?php echo esc_attr((string) $selected_preview_values['image_url']); ?>" style="width:100%;" placeholder="https://...">
            <span style="display:block; margin-top:4px; color:#50575e;">Voce pode selecionar na biblioteca de midia ou colar uma URL direta.</span>
          </p>

          <p style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" class="button" id="fcrs-select-preview-image">Escolher imagem</button>
            <button type="button" class="button" id="fcrs-remove-preview-image">Remover imagem</button>
          </p>

          <div id="fcrs-preview-image-frame" style="<?php echo !empty($selected_preview_values['image_url']) ? '' : 'display:none;'; ?> margin:16px 0;">
            <img id="fcrs-preview-image-display" src="<?php echo esc_url((string) $selected_preview_values['image_url']); ?>" alt="Preview do artigo" style="display:block; max-width:100%; width:min(520px, 100%); border-radius:16px; border:1px solid #dcdcde; background:#f6f7f7;">
          </div>

          <p style="margin-bottom:0;">
            <button type="submit" class="button button-primary">Salvar preview do artigo</button>
          </p>
        </form>
      </div>

      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var selectButton = document.getElementById('fcrs-select-preview-image');
          var removeButton = document.getElementById('fcrs-remove-preview-image');
          var imageIdInput = document.getElementById('fcrs-preview-image-id');
          var imageUrlInput = document.getElementById('fcrs-preview-image-url');
          var imageFrame = document.getElementById('fcrs-preview-image-frame');
          var imageDisplay = document.getElementById('fcrs-preview-image-display');
          var mediaFrame;

          function updatePreview(url, id) {
            imageUrlInput.value = url || '';
            imageIdInput.value = id || '';

            if (url) {
              imageDisplay.src = url;
              imageFrame.style.display = 'block';
              return;
            }

            imageDisplay.removeAttribute('src');
            imageFrame.style.display = 'none';
          }

          if (selectButton) {
            selectButton.addEventListener('click', function () {
              if (mediaFrame) {
                mediaFrame.open();
                return;
              }

              mediaFrame = wp.media({
                title: 'Escolher imagem do preview',
                button: { text: 'Usar esta imagem' },
                multiple: false
              });

              mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                updatePreview(attachment.url || '', attachment.id || '');
              });

              mediaFrame.open();
            });
          }

          if (removeButton) {
            removeButton.addEventListener('click', function () {
              updatePreview('', '');
            });
          }

          if (imageUrlInput) {
            imageUrlInput.addEventListener('input', function () {
              if (!imageUrlInput.value) {
                updatePreview('', '');
                return;
              }

              imageIdInput.value = '';
              imageDisplay.src = imageUrlInput.value;
              imageFrame.style.display = 'block';
            });
          }
        });
      </script>
    <?php else : ?>
      <div style="padding:16px; border:1px solid #dcdcde; background:#fff; margin:16px 0;">
        <p style="margin:0;">Nenhum artigo ou pagina encontrado para configurar.</p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
