<?php

defined('ABSPATH') || exit;
?>
<p>
  <label>
    <input type="checkbox" name="fcrs_enabled" value="1" <?php checked($article_config['enabled']); ?>>
    Ativar gerador neste artigo
  </label>
</p>

<p>
  <label for="fcrs-template-key"><strong>Template</strong></label><br>
  <select id="fcrs-template-key" name="fcrs_template_key" style="width:100%;" <?php disabled(empty($templates)); ?>>
    <option value="">Selecione...</option>
    <?php foreach ($templates as $template_key => $template_data) : ?>
      <option value="<?php echo esc_attr($template_key); ?>" <?php selected($article_config['template_key'], $template_key); ?>>
        <?php echo esc_html((string) $template_data['label']); ?>
      </option>
    <?php endforeach; ?>
  </select>
</p>

<?php if (empty($templates)) : ?>
  <p>
    Nenhum template foi criado ainda. Va em <strong>Ferramentas &gt; Menezes Studio</strong> e cadastre os templates primeiro.
  </p>
<?php endif; ?>

<p>
  <label for="fcrs-model-override"><strong>Modelo</strong> (opcional)</label><br>
  <select id="fcrs-model-override" name="fcrs_model_override" style="width:100%;">
    <option value="">Usar modelo padrao do template</option>
    <?php foreach ($models as $model_key => $model_label) : ?>
      <option value="<?php echo esc_attr($model_key); ?>" <?php selected($article_config['model_override'], $model_key); ?>>
        <?php echo esc_html($model_label); ?>
      </option>
    <?php endforeach; ?>
  </select>
</p>

<p style="margin-bottom:0;">
  O visitante nao escolhe template nem escreve prompt. O artigo fica preso ao template criado na home do plugin e selecionado aqui.
</p>
