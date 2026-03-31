<?php

defined('ABSPATH') || exit;

class FCRS_Plugin {
  const GENERATE_ACTION = 'fcrs_generate_image';
  const JOB_STATUS_ACTION = 'fcrs_job_status';
  const DOWNLOAD_ACTION = 'fcrs_download_result';
  const SAVE_SETTINGS_ACTION = 'fcrs_save_settings';
  const SAVE_TEMPLATE_ACTION = 'fcrs_save_template';
  const DELETE_TEMPLATE_ACTION = 'fcrs_delete_template';
  const SAVE_ARTICLE_PREVIEW_ACTION = 'fcrs_save_article_preview';
  const NONCE = 'fcrs_generate_nonce';
  const STATUS_NONCE = 'fcrs_job_status_nonce';
  const SETTINGS_NONCE = 'fcrs_save_settings_nonce';
  const TEMPLATE_NONCE = 'fcrs_save_template_nonce';
  const DELETE_TEMPLATE_NONCE = 'fcrs_delete_template_nonce';
  const ARTICLE_PREVIEW_NONCE = 'fcrs_save_article_preview_nonce';
  const META_NONCE = 'fcrs_article_meta_nonce';
  const SHORTCODE = 'felipe_cloud_run_studio';
  const RESULT_PREFIX = 'fcrs_result_';
  const PAGE_SLUG = 'felipe-cloud-run-studio';
  const OPTION_KEY = 'fcrs_settings';
  const TEMPLATES_OPTION_KEY = 'fcrs_templates';
  const TEMPLATE_GENERATION_COUNT_OPTION_KEY = 'fcrs_template_generation_counts';
  const TEMPLATE_GENERATION_COUNT_MIGRATION_OPTION_KEY = 'fcrs_template_generation_counts_migrated';
  const POST_META_TEMPLATE = '_fcrs_template_key';
  const POST_META_MODEL = '_fcrs_model_override';
  const POST_META_ENABLED = '_fcrs_enabled';
  const POST_META_PREVIEW_TITLE = '_fcrs_preview_title';
  const POST_META_PREVIEW_SUBTITLE = '_fcrs_preview_subtitle';
  const POST_META_PREVIEW_IMAGE_ID = '_fcrs_preview_image_id';
  const POST_META_PREVIEW_IMAGE_URL = '_fcrs_preview_image_url';
  const DEFAULT_IMAGE_FORMAT = 'image/png';
  const DEFAULT_IMAGE_SIZE = '1K';
  const DEFAULT_ASPECT_RATIO = '1:1';
  const DEFAULT_JPEG_QUALITY = 75;
  const DEFAULT_PROCESS_TYPE = '1';
  const RESULT_TTL = 7200;
  const MAX_REFERENCE_IMAGES = 7;
  const MAX_REFERENCE_IMAGE_DIMENSION = 1400;
  const MAX_REFERENCE_IMAGE_BYTES = 700000;
  const MAX_REFERENCE_PAYLOAD_BYTES = 5000000;
  const DEFAULT_TEXT_PARAMETER_WORD_LIMIT = 3;
  const DEFAULT_TEXT_PARAMETER_LETTER_LIMIT = 15;
  const MAX_TEXT_PARAMETER_WORD_LIMIT = 50;
  const MAX_TEXT_PARAMETER_LETTER_LIMIT = 100;

  /**
   * @var FCRS_Plugin|null
   */
  private static $instance = null;

  /**
   * @return array<string, string>
   */
  private function get_template_languages() {
    return array(
      'pt_BR' => 'Português (Brasil)',
      'es_ES' => 'Español',
      'en_US' => 'English',
    );
  }

  /**
   * @param string $language
   * @return string
   */
  private function normalize_interface_language($language) {
    $language = (string) $language;
    $languages = $this->get_template_languages();

    return isset($languages[$language]) ? $language : 'pt_BR';
  }

  /**
   * @return array<string, array<string, string>>
   */
  private function get_template_color_options() {
    return array(
      'blue' => array(
        'label' => 'Azul',
        'accent' => '#2563eb',
        'accent_dark' => '#1d4ed8',
        'soft' => '#f8fbff',
        'soft_strong' => '#dbeafe',
        'border' => '#bfdbfe',
      ),
      'pink' => array(
        'label' => 'Rosa',
        'accent' => '#db2777',
        'accent_dark' => '#be185d',
        'soft' => '#fdf2f8',
        'soft_strong' => '#fbcfe8',
        'border' => '#f9a8d4',
      ),
      'purple' => array(
        'label' => 'Roxo',
        'accent' => '#7c3aed',
        'accent_dark' => '#6d28d9',
        'soft' => '#f5f3ff',
        'soft_strong' => '#ddd6fe',
        'border' => '#c4b5fd',
      ),
      'green' => array(
        'label' => 'Verde',
        'accent' => '#16a34a',
        'accent_dark' => '#15803d',
        'soft' => '#f0fdf4',
        'soft_strong' => '#bbf7d0',
        'border' => '#86efac',
      ),
    );
  }

  /**
   * @param string $color_key
   * @return string
   */
  private function normalize_template_color($color_key) {
    $color_key = sanitize_key((string) $color_key);
    $options = $this->get_template_color_options();

    return isset($options[$color_key]) ? $color_key : 'blue';
  }

  /**
   * @param string $color_key
   * @return array<string, string>
   */
  private function get_template_color_palette($color_key) {
    $normalized_key = $this->normalize_template_color($color_key);
    $options = $this->get_template_color_options();

    return $options[$normalized_key];
  }

  /**
   * @param array<string, string> $palette
   * @return string
   */
  private function build_template_color_style(array $palette) {
    return sprintf(
      '--fcrs-accent:%1$s; --fcrs-accent-dark:%2$s; --fcrs-soft:%3$s; --fcrs-soft-strong:%4$s; --fcrs-soft-border:%5$s;',
      $palette['accent'],
      $palette['accent_dark'],
      $palette['soft'],
      $palette['soft_strong'],
      $palette['border']
    );
  }

  /**
   * @param string $language
   * @return array<string, mixed>
   */
  private function get_interface_strings($language) {
    $language = $this->normalize_interface_language($language);

    if ('es_ES' === $language) {
      return array(
        'tip' => 'Consejo: usa fotos nítidas, con buena iluminación y poco filtro. Si hay rostro, intenta enviar imágenes donde aparezca bien.',
        'loading_title' => 'Generando tu imagen',
        'loading_messages' => array(
          'Estamos generando tu imagen con mucho cuidado.',
          'Organizando luz, encuadre y detalles finales.',
          'Eligiendo los mejores detalles para que todo se vea aún mejor.',
          'Preparando un resultado bonito para mostrarte aquí.',
          'Ajustando colores, contraste y expresión con bastante cuidado.',
          'Casi listo: tu imagen está recibiendo los últimos retoques.',
          'Dando un toque especial para que tu imagen se vea aún más increíble.',
          'Refinando cada detalle para entregarte un resultado muy bonito.',
        ),
        'waiting_message' => 'Tu pedido fue enviado. Espera solo un poco más.',
        'poll_failed_message' => 'No se pudo consultar el progreso del proceso.',
        'query_failed_message' => 'No se pudo consultar el proceso.',
        'text_parameter_tip' => 'Consejo: evita simbolos, escribe con claridad y usa palabras cortas para obtener un mejor resultado.',
        'temporary_result' => 'Resultado temporal',
        'until' => 'hasta',
        'media_library' => 'Biblioteca multimedia',
        'generate_label' => 'Generar imagen',
        'download_label' => 'Descargar imagen',
        'download_retry_label' => 'Descargar nuevamente',
        'rewarded_loading_label' => 'Abriendo anuncio...',
        'generate_again_label' => 'Generar otra imagen',
        'rewarded_download_notice' => 'Para recibir tu recompensa de descarga, necesitas ver un pequeno anuncio. Despues de eso, la descarga se iniciara automaticamente.',
        'upload_label' => 'Sube tus fotos',
        'upload_required' => 'Esta plantilla requiere al menos una foto.',
        'upload_optional' => 'La foto es opcional para esta plantilla.',
        'upload_subhint' => 'Puedes enviar hasta %d imágenes.',
        'parameter_input_label' => 'Completa el campo',
        'parameter_input_placeholder' => 'Escribe aqui',
        'parameter_input_hint' => 'Esta plantilla usa el marcador %s dentro del prompt.',
        'parameter_limits' => 'Maximo de %1$d palabras y hasta %2$d letras por palabra.',
        'parameter_required_message' => 'Completa el campo %label%.',
        'parameter_word_limit_message' => 'El campo %label% acepta como maximo %limit% palabras.',
        'parameter_letter_limit_message' => 'Cada palabra del campo %label% puede tener como maximo %limit% letras.',
        'selected_photos' => 'Fotos seleccionadas',
        'result_image_label' => 'Imagen generada',
        'generated_count_zero' => 'Todavia no se ha generado ninguna imagen con esta plantilla.',
        'generated_count_singular' => 'Ya se ha generado %d imagen con esta plantilla.',
        'generated_count_plural' => 'Ya se han generado %d imagenes con esta plantilla.',
      );
    }

    if ('en_US' === $language) {
      return array(
        'tip' => 'Tip: use sharp photos, with good lighting and light filtering. If there is a face, try to send images where it appears clearly.',
        'loading_title' => 'Generating your image',
        'loading_messages' => array(
          'We are generating your image with great care.',
          'Organizing lighting, framing and final details.',
          'Choosing the best details to make everything look even better.',
          'Preparing a polished result to appear here.',
          'Adjusting colors, contrast and expression with extra care.',
          'Almost there: your image is getting the final touches.',
          'Adding a special touch to make your image look even more amazing.',
          'Refining every detail to deliver a beautiful result.',
        ),
        'waiting_message' => 'Your request was sent. Please wait just a little longer.',
        'poll_failed_message' => 'Failed to check the job progress.',
        'query_failed_message' => 'Failed to check the job.',
        'text_parameter_tip' => 'Tip: avoid symbols, write clearly and prefer short words for a cleaner result.',
        'temporary_result' => 'Temporary result',
        'until' => 'until',
        'media_library' => 'Media library',
        'generate_label' => 'Generate image',
        'download_label' => 'Download image',
        'download_retry_label' => 'Download again',
        'rewarded_loading_label' => 'Opening ad...',
        'generate_again_label' => 'Generate another image',
        'rewarded_download_notice' => 'To receive your download reward, you need to watch a short ad. After that, the download will start automatically.',
        'upload_label' => 'Upload your photos',
        'upload_required' => 'This template requires at least one photo.',
        'upload_optional' => 'A photo is optional for this template.',
        'upload_subhint' => 'You can upload up to %d images.',
        'parameter_input_label' => 'Fill in the field',
        'parameter_input_placeholder' => 'Type here',
        'parameter_input_hint' => 'This template uses the %s marker inside the prompt.',
        'parameter_limits' => 'Maximum of %1$d words and up to %2$d letters per word.',
        'parameter_required_message' => 'Fill in the %label% field.',
        'parameter_word_limit_message' => 'The %label% field accepts at most %limit% words.',
        'parameter_letter_limit_message' => 'Each word in the %label% field can have at most %limit% letters.',
        'selected_photos' => 'Selected photos',
        'result_image_label' => 'Generated image',
        'generated_count_zero' => 'No images have been generated with this template yet.',
        'generated_count_singular' => '%d image has already been generated with this template.',
        'generated_count_plural' => '%d images have already been generated with this template.',
      );
    }

    return array(
      'tip' => 'Dica: use fotos nítidas, com boa iluminação e pouco filtro. Se houver rosto, tente enviar imagens em que ele apareça bem.',
      'loading_title' => 'Gerando sua imagem',
      'loading_messages' => array(
        'Estamos gerando a sua imagem com muito carinho.',
        'Organizando luz, enquadramento e detalhes finais.',
        'Escolhendo os melhores detalhes para deixar tudo ainda mais bonito.',
        'Preparando um resultado caprichado para aparecer aqui.',
        'Ajustando cores, contraste e expressão com bastante cuidado.',
        'Quase lá: sua imagem está recebendo os últimos retoques.',
        'Dando um toque especial para a sua imagem ficar ainda mais incrível.',
        'Refinando cada detalhe para entregar um resultado bem bonito.',
      ),
      'waiting_message' => 'Seu pedido foi enviado. Aguarde só mais um pouquinho.',
      'poll_failed_message' => 'Falha ao consultar o andamento do job.',
      'query_failed_message' => 'Falha ao consultar o job.',
      'text_parameter_tip' => 'Dica: evite utilizar simbolos, escreva com clareza e prefira palavras curtas para um resultado melhor.',
      'temporary_result' => 'Resultado temporário',
      'until' => 'até',
      'media_library' => 'Biblioteca de mídia',
      'generate_label' => 'Gerar imagem',
      'download_label' => 'Baixar imagem',
      'download_retry_label' => 'Baixar novamente',
      'rewarded_loading_label' => 'Abrindo anuncio...',
      'generate_again_label' => 'Gerar outra imagem',
      'parameter_input_label' => 'Preencha o campo',
      'parameter_input_placeholder' => 'Digite aqui',
      'parameter_input_hint' => 'Este template usa o marcador %s dentro do prompt.',
      'parameter_limits' => 'Maximo de %1$d palavras e ate %2$d letras por palavra.',
      'parameter_required_message' => 'Preencha o campo %label%.',
      'parameter_word_limit_message' => 'O campo %label% aceita no maximo %limit% palavras.',
      'parameter_letter_limit_message' => 'Cada palavra do campo %label% pode ter no maximo %limit% letras.',
      'rewarded_download_notice' => 'Para receber sua recompensa de download, e necessario assistir um pequeno anuncio. Depois disso, o download sera iniciado automaticamente.',
      'upload_label' => 'Envie suas fotos',
      'upload_required' => 'Este template exige pelo menos uma foto.',
      'upload_optional' => 'A foto é opcional para este template.',
      'upload_subhint' => 'Você pode enviar até %d imagens.',
      'selected_photos' => 'Fotos selecionadas',
      'result_image_label' => 'Imagem gerada',
      'generated_count_zero' => 'Ainda nao ha imagens geradas com este template.',
      'generated_count_singular' => 'Ja foi gerada %d imagem com este template.',
      'generated_count_plural' => 'Ja foram geradas %d imagens com este template.',
    );
  }

  /**
   * @param array<string, mixed> $fallback
   * @return array<string, array<string, string>>
   */
  private function get_default_template_translations(array $fallback = array()) {
    $translations = array();

    foreach ($this->get_template_languages() as $locale => $label) {
      $translations[$locale] = array(
        'label' => !empty($fallback['label']) && 'pt_BR' === $locale ? (string) $fallback['label'] : '',
        'description' => !empty($fallback['description']) && 'pt_BR' === $locale ? (string) $fallback['description'] : '',
        'locked_prompt' => !empty($fallback['locked_prompt']) && 'pt_BR' === $locale ? (string) $fallback['locked_prompt'] : '',
      );
    }

    return $translations;
  }

  /**
   * @param mixed $translations
   * @param array<string, mixed> $fallback
   * @return array<string, array<string, string>>
   */
  private function sanitize_template_translations($translations, array $fallback = array()) {
    $normalized = $this->get_default_template_translations($fallback);

    if (!is_array($translations)) {
      return $normalized;
    }

    foreach ($this->get_template_languages() as $locale => $language_label) {
      $data = !empty($translations[$locale]) && is_array($translations[$locale]) ? $translations[$locale] : array();

      $normalized[$locale] = array(
        'label' => !empty($data['label']) ? sanitize_text_field((string) $data['label']) : $normalized[$locale]['label'],
        'description' => !empty($data['description']) ? sanitize_text_field((string) $data['description']) : $normalized[$locale]['description'],
        'locked_prompt' => !empty($data['locked_prompt']) ? sanitize_textarea_field((string) $data['locked_prompt']) : $normalized[$locale]['locked_prompt'],
      );
    }

    return $normalized;
  }

  /**
   * @param array<string, mixed> $template
   * @param string $field
   * @param string $locale
   * @return string
   */
  private function get_localized_template_field(array $template, $field, $locale = '') {
    $resolved_locale = $locale ? $locale : $this->resolve_template_language();

    if (!empty($template['translations'][$resolved_locale][$field])) {
      return (string) $template['translations'][$resolved_locale][$field];
    }

    return !empty($template[$field]) ? (string) $template[$field] : '';
  }

  /**
   * @return string
   */
  private function resolve_template_language() {
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
    $locale = is_string($locale) ? strtolower($locale) : '';

    if (0 === strpos($locale, 'pt')) {
      return 'pt_BR';
    }

    if (0 === strpos($locale, 'es')) {
      return 'es_ES';
    }

    return 'en_US';
  }

  /**
   * @return FCRS_Plugin
   */
  public static function get_instance() {
    if (null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  private function __construct() {
    add_shortcode(self::SHORTCODE, array($this, 'render_shortcode'));
    add_action('admin_post_' . self::GENERATE_ACTION, array($this, 'handle_request'));
    add_action('admin_post_nopriv_' . self::GENERATE_ACTION, array($this, 'handle_request'));
    add_action('admin_post_' . self::DOWNLOAD_ACTION, array($this, 'handle_result_download'));
    add_action('admin_post_nopriv_' . self::DOWNLOAD_ACTION, array($this, 'handle_result_download'));
    add_action('wp_ajax_' . self::JOB_STATUS_ACTION, array($this, 'handle_job_status'));
    add_action('wp_ajax_nopriv_' . self::JOB_STATUS_ACTION, array($this, 'handle_job_status'));
    add_action('admin_post_' . self::SAVE_SETTINGS_ACTION, array($this, 'handle_settings_save'));
    add_action('admin_post_' . self::SAVE_TEMPLATE_ACTION, array($this, 'handle_template_save'));
    add_action('admin_post_' . self::DELETE_TEMPLATE_ACTION, array($this, 'handle_template_delete'));
    add_action('admin_post_' . self::SAVE_ARTICLE_PREVIEW_ACTION, array($this, 'handle_article_preview_save'));
    add_action('admin_menu', array($this, 'register_admin_page'));
    add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
    add_action('save_post', array($this, 'save_article_meta'));
    add_action('init', array($this, 'register_blocks'));
    add_action('wp_head', array($this, 'render_article_preview_meta'), 1);
  }

  public function register_admin_page() {
    add_management_page(
      'Menezes Studio',
      'Menezes Studio',
      'manage_options',
      self::PAGE_SLUG,
      array($this, 'render_admin_page')
    );
  }

  public function register_meta_boxes() {
    $screens = array('post', 'page');

    foreach ($screens as $screen) {
      add_meta_box(
        'fcrs-article-config',
        'Menezes Studio',
        array($this, 'render_article_meta_box'),
        $screen,
        'side',
        'default'
      );
    }
  }

  public function register_blocks() {
    wp_register_script(
      'fcrs-block-editor',
      FCRS_PLUGIN_URL . 'assets/fcrs-block-editor.js',
      array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'),
      '1.8.35',
      true
    );

    wp_localize_script(
      'fcrs-block-editor',
      'FCRSBlockData',
      array(
        'templates' => $this->get_block_template_options(),
        'models' => $this->get_block_model_options(),
      )
    );

    register_block_type(
      'felipe-ai-studio/generator',
      array(
        'api_version' => 2,
        'editor_script' => 'fcrs-block-editor',
        'render_callback' => array($this, 'render_generator_block'),
        'attributes' => array(
          'templateKey' => array(
            'type' => 'string',
            'default' => '',
          ),
          'model' => array(
            'type' => 'string',
            'default' => '',
          ),
          'title' => array(
            'type' => 'string',
            'default' => 'Gerador de imagem',
          ),
          'submitLabel' => array(
            'type' => 'string',
            'default' => 'Gerar imagem',
          ),
        ),
      )
    );
  }

  /**
   * @param \WP_Post $post
   * @return void
   */
  public function render_article_meta_box($post) {
    $article_config = $this->get_article_config($post->ID);
    $templates = $this->get_templates_by_type('image');
    $models = $this->get_models_by_type('image');

    wp_nonce_field(self::META_NONCE, 'fcrs_article_meta_nonce');
    include FCRS_PLUGIN_DIR . 'templates/article-meta-box-v3.php';
  }

  /**
   * @param int $post_id
   * @return void
   */
  public function save_article_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (wp_is_post_revision($post_id)) {
      return;
    }

    if (!isset($_POST['fcrs_article_meta_nonce'])) {
      return;
    }

    if (!wp_verify_nonce((string) wp_unslash($_POST['fcrs_article_meta_nonce']), self::META_NONCE)) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    $enabled = !empty($_POST['fcrs_enabled']) ? '1' : '0';
    $template_key = isset($_POST['fcrs_template_key'])
      ? sanitize_key(wp_unslash($_POST['fcrs_template_key']))
      : '';
    $model_override = isset($_POST['fcrs_model_override'])
      ? sanitize_text_field((string) wp_unslash($_POST['fcrs_model_override']))
      : '';

    $templates = $this->get_templates_by_type('image');
    $models = $this->get_models_by_type('image');

    if (!array_key_exists($template_key, $templates)) {
      $template_key = '';
    }

    if ($model_override && !array_key_exists($model_override, $models)) {
      $model_override = '';
    }

    update_post_meta($post_id, self::POST_META_ENABLED, $enabled);
    update_post_meta($post_id, self::POST_META_TEMPLATE, $template_key);
    update_post_meta($post_id, self::POST_META_MODEL, $model_override);
  }

  /**
   * @param array<string, mixed> $atts
   * @return string
   */
  public function render_shortcode($atts) {
    $atts = shortcode_atts(
      array(
        'title' => 'Gerador de imagem',
        'submit_label' => 'Gerar imagem',
      ),
      $atts,
      self::SHORTCODE
    );

    $post_id = get_queried_object_id();

    if (!$post_id) {
      return '';
    }

    $article_config = $this->get_article_config($post_id);

    if (!$article_config['enabled'] || empty($article_config['template_key'])) {
      if (current_user_can('edit_post', $post_id)) {
        return '<p>Menezes Studio: selecione um template neste artigo para exibir o gerador.</p>';
      }

      return '';
    }

    $template = $this->get_template($article_config['template_key']);

    if (!$template) {
      if (current_user_can('edit_post', $post_id)) {
        return '<p>Menezes Studio: o template selecionado neste artigo nao existe mais.</p>';
      }

      return '';
    }

    return $this->render_generator_panel(
      $article_config['template_key'],
      $article_config['model_override'],
      array(
        'title' => (string) $atts['title'],
        'submit_label' => (string) $atts['submit_label'],
        'description' => '',
      )
    );
  }

  /**
   * @param array<string, mixed> $attributes
   * @return string
   */
  public function render_generator_block($attributes) {
    $template_key = !empty($attributes['templateKey'])
      ? sanitize_key((string) $attributes['templateKey'])
      : '';
    $model_override = !empty($attributes['model'])
      ? sanitize_text_field((string) $attributes['model'])
      : '';
    $title = !empty($attributes['title'])
      ? sanitize_text_field((string) $attributes['title'])
      : 'Gerador de imagem';
    $submit_label = !empty($attributes['submitLabel'])
      ? sanitize_text_field((string) $attributes['submitLabel'])
      : 'Gerar imagem';

    if ('' === $template_key) {
      if (is_admin() && current_user_can('edit_posts')) {
        return '<p>Menezes Studio: selecione um template no bloco.</p>';
      }

      return '';
    }

    $template = $this->get_template($template_key);

    if (!$template) {
      if (is_admin() && current_user_can('edit_posts')) {
        return '<p>Menezes Studio: o template selecionado no bloco nao existe mais.</p>';
      }

      return '';
    }

    return $this->render_generator_panel(
      $template_key,
      $model_override,
      array(
        'title' => $title,
        'submit_label' => $submit_label,
        'description' => '',
      )
    );
  }

  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      return;
    }

    $this->enqueue_assets();
    wp_enqueue_media();

    $config = $this->get_config();
    $saved_settings = $this->get_saved_settings();
    $templates = $this->get_templates();
    $image_templates = $this->get_templates_by_type('image');
    $models_by_type = $this->get_models_catalog();
    $result = $this->get_result();
    $result_token = $this->get_result_token();
    $editing_template_key = isset($_GET['fcrs_edit_template'])
      ? sanitize_key(wp_unslash($_GET['fcrs_edit_template']))
      : '';
    $editing_template = $editing_template_key ? $this->get_template($editing_template_key) : null;
    $selected_generation_type = isset($_GET['fcrs_generation_type'])
      ? sanitize_key(wp_unslash($_GET['fcrs_generation_type']))
      : 'image';
    $selected_template_key = isset($_GET['fcrs_template'])
      ? sanitize_key(wp_unslash($_GET['fcrs_template']))
      : '';
    $selected_model = isset($_GET['fcrs_model'])
      ? sanitize_text_field((string) wp_unslash($_GET['fcrs_model']))
      : '';
    $selected_prompt = !empty($result['input_prompt'])
      ? (string) $result['input_prompt']
      : '';
    $selected_parameter_value = !empty($result['input_parameter_value'])
      ? (string) $result['input_parameter_value']
      : '';
    $selected_image_options = !empty($result['input_image_options']) && is_array($result['input_image_options'])
      ? $result['input_image_options']
      : $this->get_default_image_options();
    $admin_download_url = ($result_token && $result && !empty($result['preview_url']))
      ? esc_url($this->get_result_download_url($result_token))
      : '';
    $save_to_media = !empty($_GET['fcrs_save_media']);
    $current_tab = isset($_GET['fcrs_tab'])
      ? sanitize_key(wp_unslash($_GET['fcrs_tab']))
      : 'main';
    $current_tab = in_array($current_tab, array('main', 'article_preview'), true)
      ? $current_tab
      : 'main';
    $preview_target_posts = $this->get_preview_target_posts();
    $selected_preview_post_id = isset($_GET['fcrs_preview_post_id'])
      ? absint(wp_unslash($_GET['fcrs_preview_post_id']))
      : (!empty($preview_target_posts[0]) ? (int) $preview_target_posts[0]->ID : 0);
    $selected_preview_post = $selected_preview_post_id ? get_post($selected_preview_post_id) : null;
    $selected_preview_values = $selected_preview_post
      ? $this->get_article_preview_config($selected_preview_post_id)
      : array(
          'title' => '',
          'subtitle' => '',
          'image_id' => 0,
          'image_url' => '',
        );
    $settings_action_url = esc_url(admin_url('admin-post.php'));
    $generate_action_url = esc_url(admin_url('admin-post.php'));
    $template_action_url = esc_url(admin_url('admin-post.php'));
    $article_preview_action_url = esc_url(admin_url('admin-post.php'));
    $template_form_values = $this->get_template_form_values($editing_template_key, $editing_template);
    $template_languages = $this->get_template_languages();
    $template_color_options = $this->get_template_color_options();
    $aspect_ratio_options = $this->get_aspect_ratio_options();
    $image_size_options = $this->get_image_size_options();
    $format_options = $this->get_format_options();
    $process_type_options = $this->get_process_type_options();

    $settings_values = array(
      'endpoint' => $saved_settings['endpoint'] ? $saved_settings['endpoint'] : $config['endpoint'],
      'secret' => $saved_settings['secret'] ? $saved_settings['secret'] : $config['secret'],
      'timeout' => $saved_settings['timeout'] ? $saved_settings['timeout'] : $config['timeout'],
      'process_type' => $saved_settings['process_type'] ? $saved_settings['process_type'] : $config['process_type'],
    );

    echo '<div class="wrap">';
    echo '<h1>Menezes Studio</h1>';

    if (!empty($_GET['fcrs_settings_saved'])) {
      echo '<div class="notice notice-success is-dismissible"><p>Configuracoes salvas.</p></div>';
    }

    include FCRS_PLUGIN_DIR . 'templates/admin-panel-v3.php';
    echo '</div>';
  }

  public function handle_settings_save() {
    if (!current_user_can('manage_options')) {
      wp_die('Voce nao tem permissao para alterar estas configuracoes.');
    }

    check_admin_referer(self::SETTINGS_NONCE, 'fcrs_settings_nonce');

    $endpoint = isset($_POST['fcrs_endpoint'])
      ? esc_url_raw(trim((string) wp_unslash($_POST['fcrs_endpoint'])))
      : '';
    $secret = isset($_POST['fcrs_secret'])
      ? trim((string) wp_unslash($_POST['fcrs_secret']))
      : '';
    $timeout = isset($_POST['fcrs_timeout'])
      ? max(30, (int) wp_unslash($_POST['fcrs_timeout']))
      : 120;
    $process_type = isset($_POST['fcrs_process_type'])
      ? $this->normalize_process_type((string) wp_unslash($_POST['fcrs_process_type']))
      : self::DEFAULT_PROCESS_TYPE;

    update_option(
      self::OPTION_KEY,
      array(
        'endpoint' => $endpoint,
        'secret' => $secret,
        'timeout' => $timeout,
        'process_type' => $process_type,
      ),
      false
    );

    wp_safe_redirect(admin_url('tools.php?page=' . self::PAGE_SLUG . '&fcrs_settings_saved=1'));
    exit;
  }

  public function handle_template_save() {
    if (!current_user_can('manage_options')) {
      wp_die('Voce nao tem permissao para alterar os templates.');
    }

    check_admin_referer(self::TEMPLATE_NONCE, 'fcrs_template_nonce');

    $templates = $this->get_templates();
    $image_models = $this->get_models_by_type('image');
    $original_key = isset($_POST['fcrs_template_original_key'])
      ? sanitize_key(wp_unslash($_POST['fcrs_template_original_key']))
      : '';
    $label = isset($_POST['fcrs_template_label'])
      ? sanitize_text_field((string) wp_unslash($_POST['fcrs_template_label']))
      : '';
    $requested_key = isset($_POST['fcrs_template_key'])
      ? sanitize_key(wp_unslash($_POST['fcrs_template_key']))
      : '';
    $description = isset($_POST['fcrs_template_description'])
      ? sanitize_text_field((string) wp_unslash($_POST['fcrs_template_description']))
      : '';
    $locked_prompt = isset($_POST['fcrs_locked_prompt'])
      ? sanitize_textarea_field((string) wp_unslash($_POST['fcrs_locked_prompt']))
      : '';
    $default_model = isset($_POST['fcrs_default_model'])
      ? sanitize_text_field((string) wp_unslash($_POST['fcrs_default_model']))
      : '';
    $require_reference = !empty($_POST['fcrs_template_require_reference']) ? 1 : 0;
    $use_text_parameter = !empty($_POST['fcrs_template_use_text_parameter']) ? 1 : 0;
    $text_parameter_name = isset($_POST['fcrs_template_text_parameter_name'])
      ? $this->normalize_text_parameter_name(wp_unslash($_POST['fcrs_template_text_parameter_name']))
      : '';
    $text_parameter_word_limit = isset($_POST['fcrs_template_text_parameter_word_limit'])
      ? $this->normalize_text_parameter_word_limit(wp_unslash($_POST['fcrs_template_text_parameter_word_limit']))
      : self::DEFAULT_TEXT_PARAMETER_WORD_LIMIT;
    $text_parameter_letter_limit = isset($_POST['fcrs_template_text_parameter_letter_limit'])
      ? $this->normalize_text_parameter_letter_limit(wp_unslash($_POST['fcrs_template_text_parameter_letter_limit']))
      : self::DEFAULT_TEXT_PARAMETER_LETTER_LIMIT;
    $use_rewarded = !empty($_POST['fcrs_template_use_rewarded']) ? 1 : 0;
    $use_template_reference_image = !empty($_POST['fcrs_template_use_reference_image']) ? 1 : 0;
    $max_reference_images = isset($_POST['fcrs_template_max_reference_images'])
      ? $this->normalize_max_reference_images(wp_unslash($_POST['fcrs_template_max_reference_images']))
      : self::MAX_REFERENCE_IMAGES;
    $template_reference_image_id = isset($_POST['fcrs_template_reference_image_id'])
      ? absint(wp_unslash($_POST['fcrs_template_reference_image_id']))
      : 0;
    $template_reference_image_url = isset($_POST['fcrs_template_reference_image_url'])
      ? esc_url_raw(trim((string) wp_unslash($_POST['fcrs_template_reference_image_url'])))
      : '';
    $ui_language = isset($_POST['fcrs_template_ui_language'])
      ? $this->normalize_interface_language((string) wp_unslash($_POST['fcrs_template_ui_language']))
      : 'pt_BR';
    $template_color = isset($_POST['fcrs_template_color'])
      ? $this->normalize_template_color((string) wp_unslash($_POST['fcrs_template_color']))
      : 'blue';
    $image_options = $this->sanitize_image_options($_POST, 'fcrs_template_');

    if ($template_reference_image_id > 0) {
      $resolved_template_reference_url = wp_get_attachment_image_url($template_reference_image_id, 'full');

      if ($resolved_template_reference_url) {
        $template_reference_image_url = esc_url_raw($resolved_template_reference_url);
      }
    }

    if (!$use_template_reference_image) {
      $template_reference_image_id = 0;
      $template_reference_image_url = '';
    }

    if (!$use_text_parameter) {
      $text_parameter_name = '';
      $text_parameter_word_limit = self::DEFAULT_TEXT_PARAMETER_WORD_LIMIT;
      $text_parameter_letter_limit = self::DEFAULT_TEXT_PARAMETER_LETTER_LIMIT;
    }

    if ('' === $label) {
      $this->redirect_admin_page(array('fcrs_template_error' => 'missing_label'));
    }

    if ('' === $locked_prompt) {
      $this->redirect_admin_page(array('fcrs_template_error' => 'missing_prompt'));
    }

    if ($use_text_parameter && '' === $text_parameter_name) {
      $this->redirect_admin_page(array('fcrs_template_error' => 'missing_parameter_name'));
    }

    if (!isset($image_models[$default_model])) {
      $default_model = 'gemini-3-pro-image-preview';
    }

    $template_key = $this->get_unique_template_key($requested_key ? $requested_key : $label, $templates, $original_key);

    if ($original_key && $original_key !== $template_key && isset($templates[$original_key])) {
      unset($templates[$original_key]);
    }

    $templates[$template_key] = array(
      'type' => 'image',
      'label' => $label,
      'description' => $description,
      'locked_prompt' => $locked_prompt,
      'default_model' => $default_model,
      'require_reference' => $require_reference,
      'use_text_parameter' => $use_text_parameter,
      'text_parameter_name' => $text_parameter_name,
      'text_parameter_word_limit' => $text_parameter_word_limit,
      'text_parameter_letter_limit' => $text_parameter_letter_limit,
      'use_rewarded' => $use_rewarded,
      'use_template_reference_image' => $use_template_reference_image,
      'template_reference_image_id' => $template_reference_image_id,
      'template_reference_image_url' => $template_reference_image_url,
      'max_reference_images' => $max_reference_images,
      'ui_language' => $ui_language,
      'template_color' => $template_color,
      'image_options' => $image_options,
    );

    update_option(self::TEMPLATES_OPTION_KEY, $this->normalize_templates($templates), false);

    $this->redirect_admin_page(
      array(
        'fcrs_template_saved' => '1',
        'fcrs_edit_template' => $template_key,
      )
    );
  }

  public function handle_template_delete() {
    if (!current_user_can('manage_options')) {
      wp_die('Voce nao tem permissao para remover templates.');
    }

    check_admin_referer(self::DELETE_TEMPLATE_NONCE, 'fcrs_delete_template_nonce');

    $template_key = isset($_POST['fcrs_template_key'])
      ? sanitize_key(wp_unslash($_POST['fcrs_template_key']))
      : '';

    if ('' === $template_key) {
      $this->redirect_admin_page(array('fcrs_template_error' => 'missing_template'));
    }

    if ($this->count_template_usage($template_key) > 0) {
      $this->redirect_admin_page(array('fcrs_template_error' => 'template_in_use'));
    }

    $templates = $this->get_templates();

    if (isset($templates[$template_key])) {
      unset($templates[$template_key]);
      update_option(self::TEMPLATES_OPTION_KEY, $this->normalize_templates($templates), false);
    }

    $this->redirect_admin_page(array('fcrs_template_deleted' => '1'));
  }

  public function handle_article_preview_save() {
    if (!current_user_can('manage_options')) {
      wp_die('Voce nao tem permissao para alterar o preview dos artigos.');
    }

    check_admin_referer(self::ARTICLE_PREVIEW_NONCE, 'fcrs_article_preview_nonce');

    $post_id = isset($_POST['fcrs_preview_post_id'])
      ? absint(wp_unslash($_POST['fcrs_preview_post_id']))
      : 0;
    $post = $post_id ? get_post($post_id) : null;

    if (!$post || !in_array($post->post_type, array('post', 'page'), true) || !current_user_can('edit_post', $post_id)) {
      $this->redirect_admin_page(array(
        'fcrs_tab' => 'article_preview',
        'fcrs_preview_error' => 'invalid_post',
      ));
    }

    $preview_title = isset($_POST['fcrs_preview_title'])
      ? sanitize_text_field((string) wp_unslash($_POST['fcrs_preview_title']))
      : '';
    $preview_subtitle = isset($_POST['fcrs_preview_subtitle'])
      ? sanitize_textarea_field((string) wp_unslash($_POST['fcrs_preview_subtitle']))
      : '';
    $preview_image_id = isset($_POST['fcrs_preview_image_id'])
      ? absint(wp_unslash($_POST['fcrs_preview_image_id']))
      : 0;
    $preview_image_url = isset($_POST['fcrs_preview_image_url'])
      ? esc_url_raw(trim((string) wp_unslash($_POST['fcrs_preview_image_url'])))
      : '';

    if ($preview_image_id > 0) {
      $resolved_image_url = wp_get_attachment_image_url($preview_image_id, 'full');

      if ($resolved_image_url) {
        $preview_image_url = esc_url_raw($resolved_image_url);
      }
    }

    if ('' !== $preview_title) {
      update_post_meta($post_id, self::POST_META_PREVIEW_TITLE, $preview_title);
    } else {
      delete_post_meta($post_id, self::POST_META_PREVIEW_TITLE);
    }

    if ('' !== $preview_subtitle) {
      update_post_meta($post_id, self::POST_META_PREVIEW_SUBTITLE, $preview_subtitle);
    } else {
      delete_post_meta($post_id, self::POST_META_PREVIEW_SUBTITLE);
    }

    if ($preview_image_id > 0) {
      update_post_meta($post_id, self::POST_META_PREVIEW_IMAGE_ID, $preview_image_id);
    } else {
      delete_post_meta($post_id, self::POST_META_PREVIEW_IMAGE_ID);
    }

    if ('' !== $preview_image_url) {
      update_post_meta($post_id, self::POST_META_PREVIEW_IMAGE_URL, $preview_image_url);
    } else {
      delete_post_meta($post_id, self::POST_META_PREVIEW_IMAGE_URL);
    }

    $this->redirect_admin_page(array(
      'fcrs_tab' => 'article_preview',
      'fcrs_preview_post_id' => $post_id,
      'fcrs_preview_saved' => '1',
    ));
  }

  public function handle_request() {
    check_admin_referer(self::NONCE, 'fcrs_nonce');

    $redirect_url = $this->get_redirect_url();
    $generation_type = isset($_POST['generationType'])
      ? sanitize_key(wp_unslash($_POST['generationType']))
      : 'image';
    $template_key = isset($_POST['templateKey'])
      ? sanitize_key(wp_unslash($_POST['templateKey']))
      : '';
    $manual_prompt = isset($_POST['manualPrompt'])
      ? sanitize_textarea_field((string) wp_unslash($_POST['manualPrompt']))
      : '';
    $selected_model = isset($_POST['model'])
      ? sanitize_text_field((string) wp_unslash($_POST['model']))
      : '';
    $request_image_options = $this->sanitize_image_options($_POST, 'fcrs_test_', false);
    $save_to_media = !empty($_POST['saveToMedia']) ? 1 : 0;
    $generation_context = isset($_POST['generationContext'])
      ? $this->normalize_generation_context(sanitize_key(wp_unslash($_POST['generationContext'])))
      : 'admin';
    $config = $this->get_config();

    if (!$config['ready']) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Configure a URL do endpoint e a chave antes de usar o plugin.',
        ),
        $template_key,
        $selected_model,
        $generation_type,
        $save_to_media
      );
    }

    if ('video' === $generation_type) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'A interface de video foi preparada, mas a API atual ainda nao suporta geracao de video.',
        ),
        $template_key,
        $selected_model,
        $generation_type,
        $save_to_media
      );
    }

    $templates = $this->get_templates();

    if ($template_key && !array_key_exists($template_key, $templates)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Template invalido.',
        ),
        $template_key,
        $selected_model,
        $generation_type,
        $save_to_media
      );
    }

    $models = $this->get_models_by_type($generation_type);
    $template = $template_key ? $templates[$template_key] : null;
    $model_to_use = $selected_model;
    $source_reference_name = $this->get_uploaded_reference_original_name();
    $ui_strings = $template
      ? $this->get_interface_strings(!empty($template['ui_language']) ? (string) $template['ui_language'] : 'pt_BR')
      : $this->get_interface_strings('pt_BR');
    $resolved_text_parameter_value = '';

    if (!$model_to_use || !array_key_exists($model_to_use, $models)) {
      $model_to_use = $template && !empty($template['default_model'])
        ? $template['default_model']
        : 'gemini-3-pro-image-preview';
    }

    $prompt_parts = array();

    if ($template && !empty($template['locked_prompt'])) {
      $locked_prompt = (string) $template['locked_prompt'];

      if ($this->template_uses_text_parameter($template)) {
        $validated_parameter = $this->validate_template_text_parameter_value(
          $template,
          isset($_POST['templateParameterValue']) ? wp_unslash($_POST['templateParameterValue']) : '',
          $ui_strings
        );

        if (is_wp_error($validated_parameter)) {
          $this->redirect_with_result(
            $redirect_url,
            array(
              'status' => 'error',
              'message' => $validated_parameter->get_error_message(),
            ),
            $template_key,
            $model_to_use,
            $generation_type,
            $save_to_media
          );
        }

        $resolved_text_parameter_value = !empty($validated_parameter['value'])
          ? (string) $validated_parameter['value']
          : '';
        $locked_prompt = str_replace(
          (string) $validated_parameter['placeholder'],
          $resolved_text_parameter_value,
          $locked_prompt
        );
      }

      $prompt_parts[] = $locked_prompt;
    }

    if ('' !== trim($manual_prompt)) {
      $prompt_parts[] = trim($manual_prompt);
    }

    if (empty($prompt_parts)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Selecione um template ou preencha um prompt para o teste.',
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $payload = array(
      'model' => $model_to_use,
      'userPrompt' => implode("\n\n", $prompt_parts),
    );
    $image_options = $this->merge_image_options(
      $template ? $this->extract_template_image_options($template) : array(),
      $request_image_options
    );
    $max_reference_images = $template
      ? $this->get_template_max_reference_images($template)
      : self::MAX_REFERENCE_IMAGES;

    $template_reference_images = $template
      ? $this->read_template_reference_images($template)
      : array();

    if (is_wp_error($template_reference_images)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => $template_reference_images->get_error_message(),
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $remaining_reference_slots = max(0, $max_reference_images - count($template_reference_images));
    $uploaded_reference_images = $remaining_reference_slots > 0
      ? $this->read_reference_images($remaining_reference_slots)
      : array();

    if (is_wp_error($uploaded_reference_images)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => $uploaded_reference_images->get_error_message(),
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $reference_images = array_merge($template_reference_images, $uploaded_reference_images);

    if ('' === $source_reference_name && $template) {
      $source_reference_name = $this->get_template_reference_original_name($template);
    }

    $download_filename = $this->build_generated_download_filename(
      !empty($image_options['outputMimeType']) ? (string) $image_options['outputMimeType'] : self::DEFAULT_IMAGE_FORMAT,
      $source_reference_name,
      $template_key
    );

    if ($template && !empty($template['require_reference']) && !$this->template_uses_text_parameter($template) && empty($reference_images)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Este template exige o envio de pelo menos uma foto.',
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $image_options = $this->resolve_reference_based_image_options($image_options, $template ? $template : array());

    if (!empty($image_options)) {
      $payload['imageOptions'] = $image_options;
    }

    if (!empty($reference_images)) {
      $payload['referenceImages'] = $reference_images;
    }

    $process_type = !empty($config['process_type'])
      ? $this->normalize_process_type((string) $config['process_type'])
      : self::DEFAULT_PROCESS_TYPE;
    $response = wp_remote_post(
      '2' === $process_type
        ? $this->build_endpoint_url($config['endpoint'], '/jobs')
        : $this->build_endpoint_url($config['endpoint']),
      array(
        'timeout' => $config['timeout'],
        'headers' => array(
          'Content-Type' => 'application/json',
          'X-Site-Secret' => $config['secret'],
        ),
        'body' => wp_json_encode($payload),
      )
    );

    if (is_wp_error($response)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Falha ao chamar o endpoint: ' . $response->get_error_message(),
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
      $message = is_array($data) && !empty($data['error'])
        ? (string) $data['error']
        : 'O endpoint respondeu com erro.';
      $details = is_array($data) && !empty($data['details'])
        ? ' ' . (string) $data['details']
        : '';
      $reason_text = is_array($data) && !empty($data['text'])
        ? wp_strip_all_tags((string) $data['text'])
        : '';

      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => trim($message . $details),
          'text' => $reason_text,
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    if ('2' === $process_type) {
      if (!is_array($data) || empty($data['jobId'])) {
        $this->redirect_with_result(
          $redirect_url,
          array(
            'status' => 'error',
            'message' => 'O endpoint nao devolveu um job valido.',
          ),
          $template_key,
          $model_to_use,
          $generation_type,
          $save_to_media
        );
      }

      $result = array(
        'status' => !empty($data['status']) ? (string) $data['status'] : 'queued',
        'message' => 'Geracao iniciada. O resultado sera atualizado automaticamente.',
        'job_id' => (string) $data['jobId'],
        'model' => $model_to_use,
        'template_key' => $template_key,
        'template_label' => $template ? $template['label'] : '',
        'text' => '',
        'input_image_options' => $image_options,
        'save_to_media' => $save_to_media ? 1 : 0,
        'process_type' => '2',
        'generation_context' => $generation_context,
        'used_reference_image' => !empty($reference_images) ? 1 : 0,
        'reference_image_count' => count($reference_images),
        'template_generation_count' => $this->get_template_generation_count($template_key),
        'download_filename' => $download_filename,
        'input_parameter_value' => $resolved_text_parameter_value,
      );

      $this->redirect_with_result($redirect_url, $result, $template_key, $model_to_use, $generation_type, $save_to_media);
    }

    if (!is_array($data) || empty($data['imageBase64']) || empty($data['mimeType'])) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'O endpoint nao devolveu uma imagem valida.',
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $preview = $this->save_preview_file((string) $data['imageBase64'], (string) $data['mimeType']);

    if (is_wp_error($preview)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => $preview->get_error_message(),
        ),
        $template_key,
        $model_to_use,
        $generation_type,
        $save_to_media
      );
    }

    $result = array(
      'status' => 'success',
      'message' => 'Imagem gerada com sucesso.',
      'model' => !empty($data['model']) ? (string) $data['model'] : $model_to_use,
      'template_key' => $template_key,
      'template_label' => $template ? $template['label'] : '',
      'preview_url' => $preview['url'],
      'mime_type' => (string) $data['mimeType'],
      'text' => !empty($data['text']) ? wp_strip_all_tags((string) $data['text']) : '',
      'input_image_options' => $image_options,
      'save_to_media' => $save_to_media ? 1 : 0,
      'remote_result' => 0,
      'temporary_result' => 0,
      'process_type' => '1',
      'generation_context' => $generation_context,
      'used_reference_image' => !empty($reference_images) ? 1 : 0,
      'reference_image_count' => count($reference_images),
      'download_url' => $preview['url'],
      'download_filename' => $download_filename,
      'input_parameter_value' => $resolved_text_parameter_value,
    );

    if ($save_to_media) {
      $attachment_id = $this->create_media_attachment($preview['file'], $preview['url'], (string) $data['mimeType']);

      if (is_wp_error($attachment_id)) {
        $result['status'] = 'warning';
        $result['message'] = 'A imagem foi gerada, mas nao foi possivel salvar na biblioteca de midia.';
        $result['text'] = trim($result['text'] . ' ' . $attachment_id->get_error_message());
      } else {
        $result['attachment_id'] = $attachment_id;
      }
    }

    $result = $this->maybe_register_template_generation($result);

    $this->redirect_with_result($redirect_url, $result, $template_key, $model_to_use, $generation_type, $save_to_media);
  }

  public function handle_job_status() {
    check_ajax_referer(self::STATUS_NONCE, 'security');

    $job_id = isset($_POST['jobId'])
      ? sanitize_text_field((string) wp_unslash($_POST['jobId']))
      : '';
    $result_token = isset($_POST['resultToken'])
      ? sanitize_text_field((string) wp_unslash($_POST['resultToken']))
      : '';

    if ('' === $job_id || '' === $result_token) {
      wp_send_json_error(array(
        'message' => 'Job invalido.',
      ), 400);
    }

    $config = $this->get_config();

    if (!$config['ready']) {
      wp_send_json_error(array(
        'message' => 'Configure o endpoint e a chave antes de consultar jobs.',
      ), 400);
    }

    $current_result = $this->get_result_by_token($result_token);

    if (!is_array($current_result)) {
      wp_send_json_error(array(
        'message' => 'O resultado local desse job expirou. Gere novamente.',
      ), 410);
    }

    $status_response = wp_remote_get(
      $this->build_endpoint_url($config['endpoint'], '/jobs/' . rawurlencode($job_id)),
      array(
        'timeout' => max(30, $config['timeout']),
        'headers' => array(
          'X-Site-Secret' => $config['secret'],
        ),
      )
    );

    if (is_wp_error($status_response)) {
      wp_send_json_error(array(
        'message' => 'Falha ao consultar o job: ' . $status_response->get_error_message(),
      ), 502);
    }

    $status_code = (int) wp_remote_retrieve_response_code($status_response);
    $status_body = wp_remote_retrieve_body($status_response);
    $status_data = json_decode($status_body, true);

    if ($status_code < 200 || $status_code >= 300 || !is_array($status_data)) {
      $message = is_array($status_data) && !empty($status_data['error'])
        ? (string) $status_data['error']
        : 'Nao foi possivel consultar o status do job.';
      $details = is_array($status_data) && !empty($status_data['details'])
        ? ' ' . (string) $status_data['details']
        : '';
      wp_send_json_error(array(
        'message' => trim($message . $details),
      ), $status_code >= 400 ? $status_code : 502);
    }

    $job_status = !empty($status_data['status']) ? (string) $status_data['status'] : 'unknown';

    if ('queued' === $job_status || 'processing' === $job_status) {
      $current_result['status'] = $job_status;
      $current_result['message'] = !empty($status_data['message'])
        ? (string) $status_data['message']
        : ('queued' === $job_status ? 'Job na fila.' : 'Job em processamento.');
      $this->store_result_by_token($result_token, $current_result);

      wp_send_json_success(array(
        'jobId' => $job_id,
        'status' => $job_status,
        'terminal' => false,
        'message' => $current_result['message'],
      ));
    }

    if ('failed' === $job_status) {
      $job_error_message = !empty($status_data['error'])
        ? (string) $status_data['error']
        : 'O job falhou.';
      $job_error_details = !empty($status_data['details'])
        ? wp_strip_all_tags((string) $status_data['details'])
        : '';

      $current_result['status'] = 'error';
      $current_result['message'] = trim($job_error_message . ($job_error_details ? ' ' . $job_error_details : ''));
      $current_result['text'] = $job_error_details;
      $this->store_result_by_token($result_token, $current_result);

      wp_send_json_success(array(
        'jobId' => $job_id,
        'status' => 'error',
        'terminal' => true,
        'message' => $current_result['message'],
        'details' => $job_error_details,
      ));
    }

    if ('completed' !== $job_status) {
      wp_send_json_error(array(
        'message' => 'Status de job nao suportado: ' . $job_status,
      ), 500);
    }

    if (!empty($current_result['preview_url'])) {
      $current_result = $this->maybe_register_template_generation($current_result);
      $current_result['download_url'] = $this->get_result_download_url($result_token);
      $this->store_result_by_token($result_token, $current_result);

      wp_send_json_success(array(
        'jobId' => $job_id,
        'status' => 'success',
        'terminal' => true,
        'message' => !empty($current_result['message']) ? (string) $current_result['message'] : 'Imagem pronta.',
        'result' => $current_result,
      ));
    }

    $resolved_result = $this->resolve_completed_job_result($config, $job_id, $status_data, $current_result);

    if (is_wp_error($resolved_result)) {
      wp_send_json_error(array(
        'message' => $resolved_result->get_error_message(),
      ), 500);
    }

    $resolved_result = $this->maybe_register_template_generation($resolved_result);
    $resolved_result['download_url'] = $this->get_result_download_url($result_token);
    $this->store_result_by_token($result_token, $resolved_result);

    wp_send_json_success(array(
      'jobId' => $job_id,
      'status' => !empty($resolved_result['status']) ? (string) $resolved_result['status'] : 'success',
      'terminal' => true,
      'message' => !empty($resolved_result['message']) ? (string) $resolved_result['message'] : 'Imagem pronta.',
      'result' => $resolved_result,
    ));
  }

  public function handle_result_download() {
    $token = isset($_REQUEST['resultToken'])
      ? sanitize_text_field(wp_unslash($_REQUEST['resultToken']))
      : '';
    $result = $this->get_result_by_token($token);

    if (!$result || empty($result['preview_url'])) {
      wp_die('Resultado nao encontrado ou expirado.', 404);
    }

    $mime_type = !empty($result['mime_type'])
      ? (string) $result['mime_type']
      : 'application/octet-stream';
    $filename = !empty($result['download_filename'])
      ? sanitize_file_name((string) $result['download_filename'])
      : 'imagem-gerada.' . $this->mime_to_extension($mime_type);

    if (!empty($result['attachment_id'])) {
      $file_path = get_attached_file((int) $result['attachment_id']);

      if ($file_path && file_exists($file_path)) {
        $this->stream_local_download($file_path, $mime_type, $filename);
      }
    }

    if (!empty($result['remote_result'])) {
      $remote_url = !empty($result['preview_url']) ? (string) $result['preview_url'] : '';

      if ($remote_url) {
        $this->stream_remote_download($remote_url, $mime_type, $filename);
      }
    }

    $local_file_path = $this->get_local_file_path_from_url(!empty($result['preview_url']) ? (string) $result['preview_url'] : '');

    if ($local_file_path && file_exists($local_file_path)) {
      $this->stream_local_download($local_file_path, $mime_type, $filename);
    }

    wp_die('Nao foi possivel localizar o arquivo para download.', 404);
  }

  /**
   * @return array<string, mixed>
   */
  private function get_saved_settings() {
    $settings = get_option(self::OPTION_KEY, array());

    if (!is_array($settings)) {
      $settings = array();
    }

    return array(
      'endpoint' => !empty($settings['endpoint']) ? (string) $settings['endpoint'] : '',
      'secret' => !empty($settings['secret']) ? (string) $settings['secret'] : '',
      'timeout' => !empty($settings['timeout']) ? max(30, (int) $settings['timeout']) : 120,
      'process_type' => !empty($settings['process_type']) ? $this->normalize_process_type((string) $settings['process_type']) : self::DEFAULT_PROCESS_TYPE,
    );
  }

  /**
   * @return array<string, mixed>
   */
  private function get_config() {
    $saved = $this->get_saved_settings();
    $endpoint = $saved['endpoint'];
    $secret = $saved['secret'];
    $timeout = $saved['timeout'];
    $process_type = $saved['process_type'];
    $source = 'plugin';

    if ('' === $endpoint) {
      if (defined('FCRS_CLOUD_RUN_ENDPOINT') && FCRS_CLOUD_RUN_ENDPOINT) {
        $endpoint = (string) FCRS_CLOUD_RUN_ENDPOINT;
        $source = 'wp-config';
      } elseif (defined('CLOUD_RUN_ENDPOINT') && CLOUD_RUN_ENDPOINT) {
        $endpoint = (string) CLOUD_RUN_ENDPOINT;
        $source = 'wp-config';
      }
    }

    if ('' === $secret) {
      if (defined('FCRS_CLOUD_RUN_SHARED_SECRET') && FCRS_CLOUD_RUN_SHARED_SECRET) {
        $secret = (string) FCRS_CLOUD_RUN_SHARED_SECRET;
      } elseif (defined('CLOUD_RUN_SHARED_SECRET') && CLOUD_RUN_SHARED_SECRET) {
        $secret = (string) CLOUD_RUN_SHARED_SECRET;
      }
    }

    if (120 === $timeout) {
      if (defined('FCRS_CLOUD_RUN_TIMEOUT') && FCRS_CLOUD_RUN_TIMEOUT) {
        $timeout = max(30, (int) FCRS_CLOUD_RUN_TIMEOUT);
      } elseif (defined('CLOUD_RUN_TIMEOUT') && CLOUD_RUN_TIMEOUT) {
        $timeout = max(30, (int) CLOUD_RUN_TIMEOUT);
      }
    }

    return array(
      'endpoint' => $endpoint,
      'secret' => $secret,
      'timeout' => $timeout,
      'process_type' => $process_type,
      'ready' => '' !== $endpoint && '' !== $secret,
      'source' => $source,
    );
  }

  /**
   * @return array<string, string>
   */
  private function get_process_type_options() {
    return array(
      '1' => '1 - Modo antigo',
      '2' => '2 - Modo novo em configuracao',
    );
  }

  /**
   * @param string $process_type
   * @return string
   */
  private function normalize_process_type($process_type) {
    return '2' === $process_type ? '2' : self::DEFAULT_PROCESS_TYPE;
  }

  /**
   * @param string $process_type
   * @return string
   */
  private function get_process_type_label($process_type) {
    $options = $this->get_process_type_options();
    $normalized = $this->normalize_process_type($process_type);

    return isset($options[$normalized]) ? $options[$normalized] : $options[self::DEFAULT_PROCESS_TYPE];
  }

  /**
   * @param string $generation_context
   * @return string
   */
  private function normalize_generation_context($generation_context) {
    return 'article' === $generation_context ? 'article' : 'admin';
  }

  /**
   * @param mixed $max_reference_images
   * @return int
   */
  private function normalize_max_reference_images($max_reference_images) {
    return max(1, min(self::MAX_REFERENCE_IMAGES, (int) $max_reference_images));
  }

  /**
   * @param mixed $parameter_name
   * @return string
   */
  private function normalize_text_parameter_name($parameter_name) {
    return sanitize_key((string) $parameter_name);
  }

  /**
   * @param mixed $word_limit
   * @return int
   */
  private function normalize_text_parameter_word_limit($word_limit) {
    return max(1, min(self::MAX_TEXT_PARAMETER_WORD_LIMIT, (int) $word_limit));
  }

  /**
   * @param mixed $letter_limit
   * @return int
   */
  private function normalize_text_parameter_letter_limit($letter_limit) {
    return max(1, min(self::MAX_TEXT_PARAMETER_LETTER_LIMIT, (int) $letter_limit));
  }

  /**
   * @param array<string, mixed> $template
   * @return array<string, mixed>
   */
  private function get_template_text_parameter(array $template) {
    $name = !empty($template['text_parameter_name'])
      ? $this->normalize_text_parameter_name($template['text_parameter_name'])
      : '';
    $enabled = !empty($template['use_text_parameter']) && '' !== $name;

    return array(
      'enabled' => $enabled,
      'name' => $name,
      'placeholder' => '' !== $name ? '{' . $name . '}' : '',
      'label' => '' !== $name ? ucwords(str_replace(array('_', '-'), ' ', $name)) : '',
      'word_limit' => array_key_exists('text_parameter_word_limit', $template)
        ? $this->normalize_text_parameter_word_limit($template['text_parameter_word_limit'])
        : self::DEFAULT_TEXT_PARAMETER_WORD_LIMIT,
      'letter_limit' => array_key_exists('text_parameter_letter_limit', $template)
        ? $this->normalize_text_parameter_letter_limit($template['text_parameter_letter_limit'])
        : self::DEFAULT_TEXT_PARAMETER_LETTER_LIMIT,
    );
  }

  /**
   * @param array<string, mixed> $template
   * @return bool
   */
  private function template_uses_text_parameter(array $template) {
    $parameter = $this->get_template_text_parameter($template);

    return !empty($parameter['enabled']);
  }

  /**
   * @param string $value
   * @return int
   */
  private function get_text_parameter_length($value) {
    return function_exists('mb_strlen')
      ? mb_strlen($value, 'UTF-8')
      : strlen($value);
  }

  /**
   * @param array<string, mixed> $template
   * @param string $raw_value
   * @param array<string, mixed> $ui_strings
   * @return array<string, string>|WP_Error
   */
  private function validate_template_text_parameter_value(array $template, $raw_value, array $ui_strings) {
    $parameter = $this->get_template_text_parameter($template);

    if (empty($parameter['enabled'])) {
      return array(
        'value' => '',
        'placeholder' => '',
      );
    }

    $value = trim(sanitize_text_field((string) $raw_value));
    $placeholder = !empty($parameter['placeholder']) ? (string) $parameter['placeholder'] : '{parametro}';

    if ('' === $value) {
      return new WP_Error(
        'fcrs_text_parameter_required',
        str_replace('%label%', $placeholder, (string) $ui_strings['parameter_required_message'])
      );
    }

    $words = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
    $words = is_array($words) ? $words : array();

    if (count($words) > (int) $parameter['word_limit']) {
      return new WP_Error(
        'fcrs_text_parameter_word_limit',
        str_replace(
          array('%label%', '%limit%'),
          array($placeholder, (string) $parameter['word_limit']),
          (string) $ui_strings['parameter_word_limit_message']
        )
      );
    }

    foreach ($words as $word) {
      if ($this->get_text_parameter_length((string) $word) > (int) $parameter['letter_limit']) {
        return new WP_Error(
          'fcrs_text_parameter_letter_limit',
          str_replace(
            array('%label%', '%limit%'),
            array($placeholder, (string) $parameter['letter_limit']),
            (string) $ui_strings['parameter_letter_limit_message']
          )
        );
      }
    }

    return array(
      'value' => implode(' ', $words),
      'placeholder' => $placeholder,
    );
  }

  /**
   * @param array<string, mixed> $template
   * @return int
   */
  private function get_template_max_reference_images(array $template) {
    if (!array_key_exists('max_reference_images', $template)) {
      return self::MAX_REFERENCE_IMAGES;
    }

    return $this->normalize_max_reference_images($template['max_reference_images']);
  }

  /**
   * @return array<string, int>
   */
  private function get_template_generation_counts() {
    global $wpdb;

    $this->ensure_template_generation_table();
    $this->maybe_migrate_template_generation_counts();

    $table_name = $this->get_template_generation_table_name();
    $rows = $wpdb->get_results("SELECT template_key, generated_count FROM {$table_name}", ARRAY_A);
    $counts = array();

    if (is_array($rows)) {
      foreach ($rows as $row) {
        $template_key = !empty($row['template_key']) ? sanitize_key((string) $row['template_key']) : '';

        if ('' === $template_key) {
          continue;
        }

        $counts[$template_key] = max(0, (int) $row['generated_count']);
      }
    }

    if (!empty($counts)) {
      return $counts;
    }

    $counts = get_option(self::TEMPLATE_GENERATION_COUNT_OPTION_KEY, array());

    if (!is_array($counts)) {
      return array();
    }

    $normalized = array();

    foreach ($counts as $template_key => $count) {
      $template_key = sanitize_key((string) $template_key);

      if ('' === $template_key) {
        continue;
      }

      $normalized[$template_key] = max(0, (int) $count);
    }

    return $normalized;
  }

  /**
   * @param string $template_key
   * @return int
   */
  private function get_template_generation_count($template_key) {
    $template_key = sanitize_key((string) $template_key);

    if ('' === $template_key) {
      return 0;
    }

    $counts = $this->get_template_generation_counts();

    return !empty($counts[$template_key]) ? (int) $counts[$template_key] : 0;
  }

  /**
   * @param string $template_key
   * @return int
   */
  private function increment_template_generation_count($template_key) {
    global $wpdb;

    $template_key = sanitize_key((string) $template_key);

    if ('' === $template_key) {
      return 0;
    }

    $this->ensure_template_generation_table();
    $this->maybe_migrate_template_generation_counts();

    $table_name = $this->get_template_generation_table_name();
    $timestamp = current_time('mysql');

    $wpdb->query(
      $wpdb->prepare(
        "INSERT INTO {$table_name} (template_key, generated_count, updated_at) VALUES (%s, 1, %s)
        ON DUPLICATE KEY UPDATE generated_count = generated_count + 1, updated_at = %s",
        $template_key,
        $timestamp,
        $timestamp
      )
    );

    return $this->get_template_generation_count($template_key);
  }

  /**
   * @return string
   */
  private function get_template_generation_table_name() {
    global $wpdb;

    return $wpdb->prefix . 'fcrs_template_stats';
  }

  /**
   * @return void
   */
  private function ensure_template_generation_table() {
    static $ready = false;

    if ($ready) {
      return;
    }

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table_name = $this->get_template_generation_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
      template_key varchar(191) NOT NULL,
      generated_count bigint(20) unsigned NOT NULL DEFAULT 0,
      updated_at datetime NOT NULL,
      PRIMARY KEY  (template_key)
    ) {$charset_collate};";

    dbDelta($sql);
    $ready = true;
  }

  /**
   * @return void
   */
  private function maybe_migrate_template_generation_counts() {
    global $wpdb;

    if (get_option(self::TEMPLATE_GENERATION_COUNT_MIGRATION_OPTION_KEY)) {
      return;
    }

    $legacy_counts = get_option(self::TEMPLATE_GENERATION_COUNT_OPTION_KEY, array());

    if (!is_array($legacy_counts) || empty($legacy_counts)) {
      update_option(self::TEMPLATE_GENERATION_COUNT_MIGRATION_OPTION_KEY, 1, false);
      return;
    }

    $table_name = $this->get_template_generation_table_name();
    $timestamp = current_time('mysql');

    foreach ($legacy_counts as $template_key => $count) {
      $template_key = sanitize_key((string) $template_key);
      $count = max(0, (int) $count);

      if ('' === $template_key || $count <= 0) {
        continue;
      }

      $wpdb->query(
        $wpdb->prepare(
          "INSERT INTO {$table_name} (template_key, generated_count, updated_at) VALUES (%s, %d, %s)
          ON DUPLICATE KEY UPDATE generated_count = GREATEST(generated_count, %d), updated_at = %s",
          $template_key,
          $count,
          $timestamp,
          $count,
          $timestamp
        )
      );
    }

    update_option(self::TEMPLATE_GENERATION_COUNT_MIGRATION_OPTION_KEY, 1, false);
  }

  /**
   * @param array<string, mixed> $ui_strings
   * @param int $count
   * @return string
   */
  private function format_template_generation_count_label(array $ui_strings, $count) {
    $count = max(0, (int) $count);

    if (0 === $count) {
      return !empty($ui_strings['generated_count_zero'])
        ? (string) $ui_strings['generated_count_zero']
        : 'Ainda nao ha imagens geradas com este template.';
    }

    $message = 1 === $count
      ? (!empty($ui_strings['generated_count_singular']) ? (string) $ui_strings['generated_count_singular'] : 'Ja foi gerada %d imagem com este template.')
      : (!empty($ui_strings['generated_count_plural']) ? (string) $ui_strings['generated_count_plural'] : 'Ja foram geradas %d imagens com este template.');

    return sprintf($message, $count);
  }

  /**
   * @param array<string, mixed> $result
   * @return array<string, mixed>
   */
  private function maybe_register_template_generation(array $result) {
    $generation_context = !empty($result['generation_context'])
      ? $this->normalize_generation_context((string) $result['generation_context'])
      : 'admin';
    $template_key = !empty($result['template_key'])
      ? sanitize_key((string) $result['template_key'])
      : '';

    if ('article' !== $generation_context || '' === $template_key) {
      if (!isset($result['template_generation_count'])) {
        $result['template_generation_count'] = $this->get_template_generation_count($template_key);
      }

      return $result;
    }

    if (!empty($result['template_generation_counted'])) {
      if (!isset($result['template_generation_count'])) {
        $result['template_generation_count'] = $this->get_template_generation_count($template_key);
      }

      return $result;
    }

    $result['template_generation_count'] = $this->increment_template_generation_count($template_key);
    $result['template_generation_counted'] = 1;

    return $result;
  }

  /**
   * @param array<string, mixed> $result
   * @return array<string, string>
   */
  private function get_result_parameters(array $result) {
    $rows = array();
    $image_options = !empty($result['input_image_options']) && is_array($result['input_image_options'])
      ? $result['input_image_options']
      : array();
    $format_options = $this->get_format_options();

    if (!empty($result['process_type'])) {
      $rows['Tipo de processo'] = $this->get_process_type_label((string) $result['process_type']);
    }

    if (!empty($result['model'])) {
      $rows['Modelo'] = (string) $result['model'];
    }

    if (!empty($result['template_label'])) {
      $rows['Template'] = (string) $result['template_label'];
    }

    if (!empty($image_options['imageSize'])) {
      $rows['Tamanho da imagem'] = (string) $image_options['imageSize'];
    }

    if (!empty($image_options['aspectRatio'])) {
      $rows['Proporcao'] = (string) $image_options['aspectRatio'];
    }

    if (!empty($image_options['outputMimeType'])) {
      $output_mime_type = (string) $image_options['outputMimeType'];
      $rows['Formato'] = !empty($format_options[$output_mime_type])
        ? (string) $format_options[$output_mime_type]
        : $output_mime_type;

      if ('image/jpeg' === $output_mime_type && isset($image_options['compressionQuality'])) {
        $rows['Qualidade JPEG'] = (string) ((int) $image_options['compressionQuality']);
      }
    }

    if (isset($result['used_reference_image'])) {
      $rows['Imagem de referencia'] = !empty($result['used_reference_image']) ? 'Sim' : 'Nao';
    }

    return $rows;
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private function get_templates() {
    $templates = get_option(self::TEMPLATES_OPTION_KEY, null);

    if (null === $templates) {
      return $this->get_default_templates();
    }

    if (!is_array($templates)) {
      return array();
    }

    return $this->normalize_templates($templates);
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private function get_default_templates() {
    return array(
      'luxury_product_ad' => array(
        'type' => 'image',
      'label' => 'Anuncio Premium de Produto',
        'description' => 'Imagem publicitaria com cara de campanha premium para produto.',
        'locked_prompt' => 'Use a premium editorial advertising direction, elegant lighting, strong product focus and polished commercial finish.',
        'ui_language' => 'pt_BR',
        'default_model' => 'gemini-3-pro-image-preview',
        'max_reference_images' => self::MAX_REFERENCE_IMAGES,
        'image_options' => array(
          'aspectRatio' => '1:1',
          'imageSize' => '2K',
          'outputMimeType' => 'image/png',
          'compressionQuality' => 75,
        ),
      ),
      'character_editorial_portrait' => array(
        'type' => 'image',
      'label' => 'Retrato Editorial de Personagem',
        'description' => 'Retrato visual forte, focado em identidade, rosto e presenca.',
        'locked_prompt' => 'Create an editorial portrait with premium lighting, strong identity preservation and polished magazine-style finishing.',
        'ui_language' => 'pt_BR',
        'default_model' => 'gemini-3-pro-image-preview',
        'max_reference_images' => self::MAX_REFERENCE_IMAGES,
        'image_options' => array(
          'aspectRatio' => '3:4',
          'imageSize' => '2K',
          'outputMimeType' => 'image/png',
          'compressionQuality' => 75,
        ),
      ),
      'social_campaign_visual' => array(
        'type' => 'image',
      'label' => 'Criativo de Campanha Social',
        'description' => 'Visual pensado para redes sociais, mais chamativo e orientado a campanha.',
        'locked_prompt' => 'Create a bold social campaign visual with strong composition, clear focal subject and conversion-oriented ad aesthetics.',
        'ui_language' => 'pt_BR',
        'default_model' => 'gemini-3-pro-image-preview',
        'max_reference_images' => self::MAX_REFERENCE_IMAGES,
        'image_options' => array(
          'aspectRatio' => '4:5',
          'imageSize' => '2K',
          'outputMimeType' => 'image/png',
          'compressionQuality' => 75,
        ),
      ),
    );
  }

  /**
   * @param mixed $templates
   * @return array<string, array<string, mixed>>
   */
  private function normalize_templates($templates) {
    if (!is_array($templates)) {
      return array();
    }

    $normalized = array();

    foreach ($templates as $key => $template) {
      $normalized_key = sanitize_key((string) $key);
      $normalized_template = $this->normalize_template($template);

      if ('' === $normalized_key || empty($normalized_template)) {
        continue;
      }

      $normalized[$normalized_key] = $normalized_template;
    }

    return apply_filters('fcrs_templates_v3', $normalized);
  }

  /**
   * @param mixed $template
   * @return array<string, mixed>
   */
  private function normalize_template($template) {
    if (!is_array($template)) {
      return array();
    }

    $image_models = $this->get_models_by_type('image');
    $type = !empty($template['type']) && 'video' === $template['type'] ? 'video' : 'image';
    $default_model = !empty($template['default_model'])
      ? sanitize_text_field((string) $template['default_model'])
      : 'gemini-3-pro-image-preview';

    if ('image' === $type && !isset($image_models[$default_model])) {
      $default_model = 'gemini-3-pro-image-preview';
    }

    return array(
      'type' => $type,
      'label' => !empty($template['label']) ? sanitize_text_field((string) $template['label']) : '',
      'description' => !empty($template['description']) ? sanitize_text_field((string) $template['description']) : '',
      'locked_prompt' => !empty($template['locked_prompt']) ? sanitize_textarea_field((string) $template['locked_prompt']) : '',
      'default_model' => $default_model,
      'require_reference' => array_key_exists('require_reference', $template) ? !empty($template['require_reference']) : true,
      'use_text_parameter' => array_key_exists('use_text_parameter', $template) ? !empty($template['use_text_parameter']) : false,
      'text_parameter_name' => !empty($template['text_parameter_name']) ? $this->normalize_text_parameter_name($template['text_parameter_name']) : '',
      'text_parameter_word_limit' => array_key_exists('text_parameter_word_limit', $template)
        ? $this->normalize_text_parameter_word_limit($template['text_parameter_word_limit'])
        : self::DEFAULT_TEXT_PARAMETER_WORD_LIMIT,
      'text_parameter_letter_limit' => array_key_exists('text_parameter_letter_limit', $template)
        ? $this->normalize_text_parameter_letter_limit($template['text_parameter_letter_limit'])
        : self::DEFAULT_TEXT_PARAMETER_LETTER_LIMIT,
      'use_rewarded' => array_key_exists('use_rewarded', $template) ? !empty($template['use_rewarded']) : true,
      'use_template_reference_image' => array_key_exists('use_template_reference_image', $template) ? !empty($template['use_template_reference_image']) : false,
      'template_reference_image_id' => !empty($template['template_reference_image_id']) ? absint($template['template_reference_image_id']) : 0,
      'template_reference_image_url' => !empty($template['template_reference_image_url']) ? esc_url_raw((string) $template['template_reference_image_url']) : '',
      'max_reference_images' => array_key_exists('max_reference_images', $template)
        ? $this->normalize_max_reference_images($template['max_reference_images'])
        : self::MAX_REFERENCE_IMAGES,
      'ui_language' => !empty($template['ui_language']) ? $this->normalize_interface_language((string) $template['ui_language']) : 'pt_BR',
      'template_color' => !empty($template['template_color']) ? $this->normalize_template_color((string) $template['template_color']) : 'blue',
      'image_options' => !empty($template['image_options']) && is_array($template['image_options'])
        ? $this->sanitize_image_options($template['image_options'], '', true)
        : $this->get_default_image_options(),
    );
  }

  /**
   * @param string $type
   * @return array<string, array<string, mixed>>
   */
  private function get_templates_by_type($type) {
    $filtered = array();

    foreach ($this->get_templates() as $key => $template) {
      if (!empty($template['type']) && $template['type'] === $type) {
        $filtered[$key] = $template;
      }
    }

    return $filtered;
  }

  /**
   * @return array<int, array<string, string>>
   */
  private function get_block_template_options() {
    $templates = $this->get_templates_by_type('image');
    $options = array();

    foreach ($templates as $template_key => $template_data) {
      $options[] = array(
        'value' => (string) $template_key,
        'label' => !empty($template_data['label']) ? (string) $template_data['label'] : (string) $template_key,
      );
    }

    return $options;
  }

  /**
   * @return array<int, array<string, string>>
   */
  private function get_block_model_options() {
    $models = $this->get_models_by_type('image');
    $options = array(
      array(
        'value' => '',
        'label' => 'Usar modelo padrao do template',
      ),
    );

    foreach ($models as $model_key => $model_label) {
      $options[] = array(
        'value' => (string) $model_key,
        'label' => (string) $model_label,
      );
    }

    return $options;
  }

  /**
   * @param string $key
   * @return array<string, mixed>|null
   */
  private function get_template($key) {
    $templates = $this->get_templates();

    return isset($templates[$key]) ? $templates[$key] : null;
  }

  /**
   * @return array<string, array<string, string>>
   */
  private function get_models_catalog() {
    return array(
      'image' => $this->get_models_by_type('image'),
      'video' => $this->get_models_by_type('video'),
    );
  }

  /**
   * @param string $type
   * @return array<string, string>
   */
  private function get_models_by_type($type) {
    if ('video' === $type) {
      return array(
        'veo-3.1-fast-generate-001' => 'Veo 3.1 Fast',
        'veo-3.1-generate-001' => 'Veo 3.1 Quality',
      );
    }

    return array(
      'gemini-3-pro-image-preview' => 'Gemini 3 Pro Image Preview',
      'gemini-2.5-flash-image' => 'Gemini 2.5 Flash Image',
      'imagen-4.0-fast-generate-001' => 'Imagen 4 Fast',
      'imagen-4.0-generate-001' => 'Imagen 4 Standard',
      'imagen-4.0-ultra-generate-001' => 'Imagen 4 Ultra',
    );
  }

  /**
   * @return array<string, string>
   */
  private function get_aspect_ratio_options() {
    return array(
      'uploaded' => 'Usar proporcao da imagem enviada',
      '1:1' => '1:1 Quadrado',
      '2:3' => '2:3 Retrato',
      '3:2' => '3:2 Paisagem',
      '3:4' => '3:4 Retrato',
      '4:3' => '4:3 Classico',
      '4:5' => '4:5 Social',
      '5:4' => '5:4 Classico',
      '9:16' => '9:16 Stories',
      '16:9' => '16:9 Widescreen',
      '21:9' => '21:9 Ultrawide',
    );
  }

  /**
   * @return array<string, string>
   */
  private function get_image_size_options() {
    return array(
      '1K' => '1K',
      '2K' => '2K',
      '4K' => '4K',
    );
  }

  /**
   * @return array<string, string>
   */
  private function get_format_options() {
    return array(
      'image/png' => 'PNG',
      'image/jpeg' => 'JPEG',
    );
  }

  /**
   * @param int $post_id
   * @return array<string, mixed>
   */
  private function get_article_config($post_id) {
    $enabled = '1' === (string) get_post_meta($post_id, self::POST_META_ENABLED, true);
    $template_key = (string) get_post_meta($post_id, self::POST_META_TEMPLATE, true);
    $model_override = (string) get_post_meta($post_id, self::POST_META_MODEL, true);

    return array(
      'enabled' => $enabled,
      'template_key' => $template_key,
      'model_override' => $model_override,
    );
  }

  /**
   * @param int $post_id
   * @return array<string, mixed>
   */
  private function get_article_preview_config($post_id) {
    $image_id = (int) get_post_meta($post_id, self::POST_META_PREVIEW_IMAGE_ID, true);
    $image_url = (string) get_post_meta($post_id, self::POST_META_PREVIEW_IMAGE_URL, true);

    if ($image_id > 0) {
      $resolved_image_url = wp_get_attachment_image_url($image_id, 'full');

      if ($resolved_image_url) {
        $image_url = (string) $resolved_image_url;
      }
    }

    return array(
      'title' => (string) get_post_meta($post_id, self::POST_META_PREVIEW_TITLE, true),
      'subtitle' => (string) get_post_meta($post_id, self::POST_META_PREVIEW_SUBTITLE, true),
      'image_id' => $image_id,
      'image_url' => $image_url ? esc_url_raw($image_url) : '',
    );
  }

  /**
   * @return array<int, \WP_Post>
   */
  private function get_preview_target_posts() {
    $posts = get_posts(array(
      'post_type' => array('post', 'page'),
      'post_status' => array('publish', 'draft', 'future', 'private'),
      'posts_per_page' => 200,
      'orderby' => 'date',
      'order' => 'DESC',
      'suppress_filters' => false,
    ));

    return is_array($posts) ? $posts : array();
  }

  /**
   * @return void
   */
  public function render_article_preview_meta() {
    if (is_admin() || !is_singular(array('post', 'page'))) {
      return;
    }

    $post_id = get_queried_object_id();

    if (!$post_id) {
      return;
    }

    $preview = $this->get_article_preview_config($post_id);
    $has_custom_preview = '' !== trim((string) $preview['title'])
      || '' !== trim((string) $preview['subtitle'])
      || '' !== trim((string) $preview['image_url']);

    if (!$has_custom_preview) {
      return;
    }

    $title = '' !== trim((string) $preview['title'])
      ? (string) $preview['title']
      : get_the_title($post_id);
    $subtitle = '' !== trim((string) $preview['subtitle'])
      ? (string) $preview['subtitle']
      : wp_strip_all_tags((string) get_the_excerpt($post_id));
    $image_url = '' !== trim((string) $preview['image_url'])
      ? (string) $preview['image_url']
      : (string) get_the_post_thumbnail_url($post_id, 'full');

    if ('' !== $title) {
      echo "\n" . '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
      echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    }

    if ('' !== $subtitle) {
      echo '<meta property="og:description" content="' . esc_attr($subtitle) . '">' . "\n";
      echo '<meta name="twitter:description" content="' . esc_attr($subtitle) . '">' . "\n";
    }

    if ('' !== $image_url) {
      echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
      echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . "\n";
      echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
      return;
    }

    echo '<meta name="twitter:card" content="summary">' . "\n";
  }

  /**
   * @param array<string, mixed> $image_options
   * @param array<string, mixed> $template
   * @return array<string, mixed>
   */
  private function resolve_reference_based_image_options(array $image_options, array $template = array()) {
    if (empty($image_options['aspectRatio']) || 'uploaded' !== (string) $image_options['aspectRatio']) {
      return $image_options;
    }

    $resolved_aspect_ratio = $this->get_uploaded_reference_aspect_ratio();

    if (!$resolved_aspect_ratio) {
      $resolved_aspect_ratio = $this->get_template_reference_aspect_ratio($template);
    }

    $image_options['aspectRatio'] = $resolved_aspect_ratio ? $resolved_aspect_ratio : self::DEFAULT_ASPECT_RATIO;

    return $image_options;
  }

  /**
   * @param array<string, mixed> $template
   * @return bool
   */
  private function template_uses_reference_image(array $template) {
    return !empty($template['use_template_reference_image'])
      && (
        !empty($template['template_reference_image_id'])
        || !empty($template['template_reference_image_url'])
      );
  }

  /**
   * @param array<string, mixed> $template
   * @return string
   */
  private function get_template_reference_image_url(array $template) {
    if (!$this->template_uses_reference_image($template)) {
      return '';
    }

    if (!empty($template['template_reference_image_id'])) {
      $resolved_url = wp_get_attachment_image_url((int) $template['template_reference_image_id'], 'full');

      if ($resolved_url) {
        return esc_url_raw((string) $resolved_url);
      }
    }

    return !empty($template['template_reference_image_url'])
      ? esc_url_raw((string) $template['template_reference_image_url'])
      : '';
  }

  /**
   * @param array<string, mixed> $template
   * @return string
   */
  private function get_template_reference_image_path(array $template) {
    if (!$this->template_uses_reference_image($template)) {
      return '';
    }

    if (!empty($template['template_reference_image_id'])) {
      $attached_file = get_attached_file((int) $template['template_reference_image_id']);

      if ($attached_file && file_exists($attached_file)) {
        return (string) $attached_file;
      }
    }

    $template_reference_image_url = $this->get_template_reference_image_url($template);

    return $template_reference_image_url
      ? $this->get_local_file_path_from_url($template_reference_image_url)
      : '';
  }

  /**
   * @param array<string, mixed> $template
   * @return array<int, array<string, string>>|WP_Error
   */
  private function read_template_reference_images(array $template) {
    if (!$this->template_uses_reference_image($template)) {
      return array();
    }

    $file_path = $this->get_template_reference_image_path($template);

    if ('' === $file_path || !file_exists($file_path)) {
      return new WP_Error(
        'fcrs_template_reference_missing',
        'A imagem de referencia fixa do template nao foi encontrada na biblioteca.'
      );
    }

    $mime_type = function_exists('mime_content_type')
      ? mime_content_type($file_path)
      : '';

    if (!$mime_type || 0 !== strpos($mime_type, 'image/')) {
      return new WP_Error(
        'fcrs_template_reference_invalid',
        'A imagem de referencia fixa do template precisa ser uma imagem valida.'
      );
    }

    $optimized_image = $this->optimize_reference_image($file_path, $mime_type);

    if (is_wp_error($optimized_image)) {
      return $optimized_image;
    }

    return array(
      array(
        'imageBase64' => base64_encode($optimized_image['binary']),
        'mimeType' => $optimized_image['mimeType'],
      ),
    );
  }

  /**
   * @param array<string, mixed> $template
   * @return string
   */
  private function get_template_reference_aspect_ratio(array $template = array()) {
    $file_path = $this->get_template_reference_image_path($template);

    return $file_path
      ? $this->get_image_aspect_ratio_from_path($file_path)
      : '';
  }

  /**
   * @return string
   */
  private function get_uploaded_reference_aspect_ratio() {
    if (empty($_FILES['referenceImage']) || !is_array($_FILES['referenceImage'])) {
      return '';
    }

    $file = $_FILES['referenceImage'];
    $tmp_names = !empty($file['tmp_name']) && is_array($file['tmp_name']) ? $file['tmp_name'] : array();
    $errors = !empty($file['error']) && is_array($file['error']) ? $file['error'] : array();

    if (empty($tmp_names) && isset($file['tmp_name']) && !is_array($file['tmp_name'])) {
      $tmp_names = array($file['tmp_name']);
      $errors = array(isset($file['error']) ? $file['error'] : UPLOAD_ERR_NO_FILE);
    }

    foreach ($tmp_names as $index => $tmp_name) {
      $error_code = isset($errors[$index]) ? (int) $errors[$index] : UPLOAD_ERR_NO_FILE;

      if (UPLOAD_ERR_OK !== $error_code || empty($tmp_name) || !file_exists($tmp_name)) {
        continue;
      }

      return $this->get_image_aspect_ratio_from_path($tmp_name);
    }

    return '';
  }

  /**
   * @return string
   */
  private function get_uploaded_reference_original_name() {
    if (empty($_FILES['referenceImage']) || !is_array($_FILES['referenceImage'])) {
      return '';
    }

    $file = $_FILES['referenceImage'];
    $names = !empty($file['name']) && is_array($file['name']) ? $file['name'] : array();
    $errors = !empty($file['error']) && is_array($file['error']) ? $file['error'] : array();

    if (empty($names) && !empty($file['name']) && !is_array($file['name'])) {
      $names = array($file['name']);
      $errors = array(isset($file['error']) ? $file['error'] : UPLOAD_ERR_NO_FILE);
    }

    foreach ($names as $index => $name) {
      $error_code = isset($errors[$index]) ? (int) $errors[$index] : UPLOAD_ERR_NO_FILE;

      if (UPLOAD_ERR_OK !== $error_code || '' === trim((string) $name)) {
        continue;
      }

      return sanitize_file_name((string) $name);
    }

    return '';
  }

  /**
   * @param array<string, mixed> $template
   * @return string
   */
  private function get_template_reference_original_name(array $template) {
    if (!$this->template_uses_reference_image($template)) {
      return '';
    }

    $file_path = $this->get_template_reference_image_path($template);

    if ($file_path && file_exists($file_path)) {
      return sanitize_file_name(wp_basename($file_path));
    }

    $reference_url = $this->get_template_reference_image_url($template);

    if ($reference_url) {
      $path = wp_parse_url($reference_url, PHP_URL_PATH);

      if (is_string($path) && '' !== $path) {
        return sanitize_file_name(wp_basename($path));
      }
    }

    return '';
  }

  /**
   * @param string $mime_type
   * @param string $source_name
   * @param string $template_key
   * @return string
   */
  private function build_generated_download_filename($mime_type, $source_name = '', $template_key = '') {
    $extension = $this->mime_to_extension($mime_type);
    $base_name = '';

    if ($source_name) {
      $path_info = pathinfo((string) $source_name);
      $base_name = !empty($path_info['filename']) ? (string) $path_info['filename'] : (string) $source_name;
    } elseif ($template_key) {
      $base_name = (string) $template_key;
    } else {
      $base_name = 'imagem';
    }

    $base_name = sanitize_title($base_name);

    if ('' === $base_name) {
      $base_name = 'imagem';
    }

    return sanitize_file_name($base_name . '_generated.' . $extension);
  }

  /**
   * @param string $file_path
   * @return string
   */
  private function get_image_aspect_ratio_from_path($file_path) {
    if ('' === $file_path || !file_exists($file_path)) {
      return '';
    }

    $image_size = @getimagesize($file_path);

    if (!is_array($image_size) || empty($image_size[0]) || empty($image_size[1])) {
      return '';
    }

    return $this->match_supported_aspect_ratio((int) $image_size[0], (int) $image_size[1]);
  }

  /**
   * @param int $width
   * @param int $height
   * @return string
   */
  private function match_supported_aspect_ratio($width, $height) {
    if ($width <= 0 || $height <= 0) {
      return self::DEFAULT_ASPECT_RATIO;
    }

    $target_ratio = $width / $height;
    $best_key = self::DEFAULT_ASPECT_RATIO;
    $best_diff = null;

    foreach (array_keys($this->get_aspect_ratio_options()) as $ratio_key) {
      if ('uploaded' === $ratio_key || false === strpos($ratio_key, ':')) {
        continue;
      }

      list($ratio_width, $ratio_height) = array_map('intval', explode(':', $ratio_key));

      if ($ratio_width <= 0 || $ratio_height <= 0) {
        continue;
      }

      $current_ratio = $ratio_width / $ratio_height;
      $current_diff = abs($target_ratio - $current_ratio);

      if (null === $best_diff || $current_diff < $best_diff) {
        $best_diff = $current_diff;
        $best_key = $ratio_key;
      }
    }

    return $best_key;
  }

  /**
   * @param string $editing_template_key
   * @param array<string, mixed>|null $editing_template
   * @return array<string, string>
   */
  private function get_template_form_values($editing_template_key, $editing_template) {
    if (is_array($editing_template)) {
      return array(
        'original_key' => $editing_template_key,
        'key' => $editing_template_key,
        'label' => !empty($editing_template['label']) ? (string) $editing_template['label'] : '',
        'description' => !empty($editing_template['description']) ? (string) $editing_template['description'] : '',
        'locked_prompt' => !empty($editing_template['locked_prompt']) ? (string) $editing_template['locked_prompt'] : '',
        'default_model' => !empty($editing_template['default_model']) ? (string) $editing_template['default_model'] : 'gemini-3-pro-image-preview',
        'require_reference' => array_key_exists('require_reference', $editing_template) ? (!empty($editing_template['require_reference']) ? '1' : '0') : '1',
        'use_text_parameter' => array_key_exists('use_text_parameter', $editing_template) ? (!empty($editing_template['use_text_parameter']) ? '1' : '0') : '0',
        'text_parameter_name' => !empty($editing_template['text_parameter_name']) ? (string) $editing_template['text_parameter_name'] : '',
        'text_parameter_word_limit' => array_key_exists('text_parameter_word_limit', $editing_template)
          ? (string) $this->normalize_text_parameter_word_limit($editing_template['text_parameter_word_limit'])
          : (string) self::DEFAULT_TEXT_PARAMETER_WORD_LIMIT,
        'text_parameter_letter_limit' => array_key_exists('text_parameter_letter_limit', $editing_template)
          ? (string) $this->normalize_text_parameter_letter_limit($editing_template['text_parameter_letter_limit'])
          : (string) self::DEFAULT_TEXT_PARAMETER_LETTER_LIMIT,
        'use_rewarded' => array_key_exists('use_rewarded', $editing_template) ? (!empty($editing_template['use_rewarded']) ? '1' : '0') : '1',
        'use_template_reference_image' => array_key_exists('use_template_reference_image', $editing_template) ? (!empty($editing_template['use_template_reference_image']) ? '1' : '0') : '0',
        'template_reference_image_id' => !empty($editing_template['template_reference_image_id']) ? (string) absint($editing_template['template_reference_image_id']) : '0',
        'template_reference_image_url' => !empty($editing_template['template_reference_image_url']) ? (string) $editing_template['template_reference_image_url'] : '',
        'max_reference_images' => (string) $this->get_template_max_reference_images($editing_template),
        'ui_language' => !empty($editing_template['ui_language']) ? $this->normalize_interface_language((string) $editing_template['ui_language']) : 'pt_BR',
        'template_color' => !empty($editing_template['template_color']) ? $this->normalize_template_color((string) $editing_template['template_color']) : 'blue',
        'aspect_ratio' => !empty($editing_template['image_options']['aspectRatio']) ? (string) $editing_template['image_options']['aspectRatio'] : self::DEFAULT_ASPECT_RATIO,
        'image_size' => !empty($editing_template['image_options']['imageSize']) ? (string) $editing_template['image_options']['imageSize'] : self::DEFAULT_IMAGE_SIZE,
        'output_mime_type' => !empty($editing_template['image_options']['outputMimeType']) ? (string) $editing_template['image_options']['outputMimeType'] : self::DEFAULT_IMAGE_FORMAT,
        'compression_quality' => !empty($editing_template['image_options']['compressionQuality']) ? (string) $editing_template['image_options']['compressionQuality'] : (string) self::DEFAULT_JPEG_QUALITY,
      );
    }

    return array(
      'original_key' => '',
      'key' => '',
      'label' => '',
      'description' => '',
      'locked_prompt' => '',
      'default_model' => 'gemini-3-pro-image-preview',
      'require_reference' => '1',
      'use_text_parameter' => '0',
      'text_parameter_name' => '',
      'text_parameter_word_limit' => (string) self::DEFAULT_TEXT_PARAMETER_WORD_LIMIT,
      'text_parameter_letter_limit' => (string) self::DEFAULT_TEXT_PARAMETER_LETTER_LIMIT,
      'use_rewarded' => '1',
      'use_template_reference_image' => '0',
      'template_reference_image_id' => '0',
      'template_reference_image_url' => '',
      'max_reference_images' => (string) self::MAX_REFERENCE_IMAGES,
      'ui_language' => 'pt_BR',
      'template_color' => 'blue',
      'aspect_ratio' => self::DEFAULT_ASPECT_RATIO,
      'image_size' => self::DEFAULT_IMAGE_SIZE,
      'output_mime_type' => self::DEFAULT_IMAGE_FORMAT,
      'compression_quality' => (string) self::DEFAULT_JPEG_QUALITY,
    );
  }

  /**
   * @return array<int, array<string, string>>|WP_Error
   */
  private function read_reference_images($max_reference_images = self::MAX_REFERENCE_IMAGES) {
    $max_reference_images = $this->normalize_max_reference_images($max_reference_images);

    if (empty($_FILES['referenceImage']) || !is_array($_FILES['referenceImage'])) {
      return array();
    }

    $file = $_FILES['referenceImage'];
    $names = !empty($file['name']) && is_array($file['name']) ? $file['name'] : array();
    $tmp_names = !empty($file['tmp_name']) && is_array($file['tmp_name']) ? $file['tmp_name'] : array();
    $sizes = !empty($file['size']) && is_array($file['size']) ? $file['size'] : array();
    $errors = !empty($file['error']) && is_array($file['error']) ? $file['error'] : array();

    if (empty($names) && isset($file['error']) && !is_array($file['error']) && UPLOAD_ERR_NO_FILE === (int) $file['error']) {
      return array();
    }

    if (empty($names) && isset($file['tmp_name']) && !is_array($file['tmp_name'])) {
      $names = array($file['name']);
      $tmp_names = array($file['tmp_name']);
      $sizes = array($file['size']);
      $errors = array($file['error']);
    }

    $images = array();
    $total_payload_bytes = 0;

    foreach ($names as $index => $name) {
      $error_code = isset($errors[$index]) ? (int) $errors[$index] : UPLOAD_ERR_NO_FILE;

      if (UPLOAD_ERR_NO_FILE === $error_code) {
        continue;
      }

      if (UPLOAD_ERR_OK !== $error_code) {
        return new WP_Error('fcrs_upload_error', 'Falha no envio da imagem de referencia.');
      }

      $tmp_name = !empty($tmp_names[$index]) ? (string) $tmp_names[$index] : '';
      $size = !empty($sizes[$index]) ? (int) $sizes[$index] : 0;

      if (!$tmp_name || $size <= 0) {
        return new WP_Error('fcrs_upload_invalid', 'Arquivo de imagem invalido.');
      }

      if ($size > 10 * MB_IN_BYTES) {
        return new WP_Error('fcrs_upload_size', 'Cada imagem de referencia precisa ter no maximo 10 MB.');
      }

      $mime_type = function_exists('mime_content_type')
        ? mime_content_type($tmp_name)
        : '';

      if (!$mime_type || 0 !== strpos($mime_type, 'image/')) {
        return new WP_Error('fcrs_upload_type', 'Envie apenas arquivos de imagem.');
      }

      $optimized_image = $this->optimize_reference_image($tmp_name, $mime_type);

      if (is_wp_error($optimized_image)) {
        return $optimized_image;
      }

      $binary = $optimized_image['binary'];
      $payload_mime_type = $optimized_image['mimeType'];
      $base64_image = base64_encode($binary);
      $total_payload_bytes += strlen($base64_image);

      if ($total_payload_bytes > self::MAX_REFERENCE_PAYLOAD_BYTES) {
        return new WP_Error(
          'fcrs_upload_payload',
          'As imagens enviadas ficaram pesadas demais juntas. Tente usar fotos um pouco menores ou com menos resolucao.'
        );
      }

      $images[] = array(
        'imageBase64' => $base64_image,
        'mimeType' => $payload_mime_type,
      );
    }

    if (count($images) > $max_reference_images) {
      return new WP_Error(
        'fcrs_upload_limit',
        'Envie no maximo ' . $max_reference_images . ' imagens de referencia.'
      );
    }

    return $images;
  }

  /**
   * @param string $file_path
   * @param string $mime_type
   * @return array<string, string>|WP_Error
   */
  private function optimize_reference_image($file_path, $mime_type) {
    $editor = wp_get_image_editor($file_path);

    if (is_wp_error($editor)) {
      $binary = file_get_contents($file_path);

      if (false === $binary) {
        return new WP_Error('fcrs_upload_read', 'Nao foi possivel ler uma das imagens de referencia.');
      }

      return array(
        'binary' => $binary,
        'mimeType' => $mime_type,
      );
    }

    $size = $editor->get_size();
    $width = !empty($size['width']) ? (int) $size['width'] : 0;
    $height = !empty($size['height']) ? (int) $size['height'] : 0;
    $max_dimension = self::MAX_REFERENCE_IMAGE_DIMENSION;

    if ($width > $max_dimension || $height > $max_dimension) {
      $editor->resize($max_dimension, $max_dimension, false);
    }

    $qualities = array(82, 74, 66);

    foreach ($qualities as $quality) {
      $temp_file = wp_tempnam('fcrs-ref-');

      if (!$temp_file) {
        break;
      }

      $editor_attempt = wp_get_image_editor($file_path);

      if (is_wp_error($editor_attempt)) {
        @unlink($temp_file);
        break;
      }

      $attempt_size = $editor_attempt->get_size();
      $attempt_width = !empty($attempt_size['width']) ? (int) $attempt_size['width'] : 0;
      $attempt_height = !empty($attempt_size['height']) ? (int) $attempt_size['height'] : 0;

      if ($attempt_width > $max_dimension || $attempt_height > $max_dimension) {
        $editor_attempt->resize($max_dimension, $max_dimension, false);
      }

      $editor_attempt->set_quality($quality);
      $saved = $editor_attempt->save($temp_file, 'image/jpeg');

      if (is_wp_error($saved) || empty($saved['path']) || !file_exists($saved['path'])) {
        @unlink($temp_file);
        continue;
      }

      $optimized_binary = file_get_contents($saved['path']);
      $optimized_size = filesize($saved['path']);
      @unlink($saved['path']);

      if (false === $optimized_binary || false === $optimized_size) {
        continue;
      }

      if ($optimized_size <= self::MAX_REFERENCE_IMAGE_BYTES) {
        return array(
          'binary' => $optimized_binary,
          'mimeType' => 'image/jpeg',
        );
      }
    }

    $fallback_file = wp_tempnam('fcrs-ref-fallback-');

    if ($fallback_file) {
      $editor->set_quality(60);
      $saved = $editor->save($fallback_file, 'image/jpeg');

      if (!is_wp_error($saved) && !empty($saved['path']) && file_exists($saved['path'])) {
        $optimized_binary = file_get_contents($saved['path']);
        @unlink($saved['path']);

        if (false !== $optimized_binary) {
          return array(
            'binary' => $optimized_binary,
            'mimeType' => 'image/jpeg',
          );
        }
      }
    }

    $binary = file_get_contents($file_path);

    if (false === $binary) {
      return new WP_Error('fcrs_upload_read', 'Nao foi possivel ler uma das imagens de referencia.');
    }

    return array(
      'binary' => $binary,
      'mimeType' => $mime_type,
    );
  }

  /**
   * @param string $image_base64
   * @param string $mime_type
   * @return array<string, string>|WP_Error
   */
  private function save_preview_file($image_base64, $mime_type) {
    $binary = base64_decode($image_base64, true);

    if (false === $binary) {
      return new WP_Error('fcrs_invalid_base64', 'A imagem retornada pelo endpoint veio em base64 invalido.');
    }

    $uploads = wp_upload_dir();

    if (!empty($uploads['error'])) {
      return new WP_Error('fcrs_upload_dir', 'Nao foi possivel acessar a pasta de uploads do WordPress.');
    }

    $directory = trailingslashit($uploads['basedir']) . 'fcrs-previews/';
    $url_base = trailingslashit($uploads['baseurl']) . 'fcrs-previews/';

    if (!wp_mkdir_p($directory)) {
      return new WP_Error('fcrs_mkdir', 'Nao foi possivel criar a pasta de preview do plugin.');
    }

    $filename = wp_unique_filename(
      $directory,
      'fcrs-' . gmdate('Ymd-His') . '.' . $this->mime_to_extension($mime_type)
    );

    $file_path = $directory . $filename;
    $bytes = file_put_contents($file_path, $binary);

    if (false === $bytes) {
      return new WP_Error('fcrs_write_preview', 'Nao foi possivel salvar a imagem gerada para preview.');
    }

    return array(
      'file' => $file_path,
      'url' => $url_base . $filename,
    );
  }

  /**
   * @param string $source_path
   * @param string $mime_type
   * @return array<string, string>|WP_Error
   */
  private function save_preview_file_from_path($source_path, $mime_type) {
    if (!file_exists($source_path)) {
      return new WP_Error('fcrs_preview_source', 'O arquivo temporario do job nao existe mais.');
    }

    $uploads = wp_upload_dir();

    if (!empty($uploads['error'])) {
      return new WP_Error('fcrs_upload_dir', 'Nao foi possivel acessar a pasta de uploads do WordPress.');
    }

    $directory = trailingslashit($uploads['basedir']) . 'fcrs-previews/';
    $url_base = trailingslashit($uploads['baseurl']) . 'fcrs-previews/';

    if (!wp_mkdir_p($directory)) {
      return new WP_Error('fcrs_mkdir', 'Nao foi possivel criar a pasta de preview do plugin.');
    }

    $filename = wp_unique_filename(
      $directory,
      'fcrs-' . gmdate('Ymd-His') . '.' . $this->mime_to_extension($mime_type)
    );

    $file_path = $directory . $filename;

    if (!copy($source_path, $file_path)) {
      return new WP_Error('fcrs_copy_preview', 'Nao foi possivel copiar a imagem do job para a pasta de preview.');
    }

    return array(
      'file' => $file_path,
      'url' => $url_base . $filename,
    );
  }

  /**
   * @param string $file_path
   * @param string $file_url
   * @param string $mime_type
   * @return int|WP_Error
   */
  private function create_media_attachment($file_path, $file_url, $mime_type) {
    $attachment = array(
      'guid' => $file_url,
      'post_mime_type' => $mime_type,
      'post_title' => preg_replace('/\\.[^.]+$/', '', wp_basename($file_path)),
      'post_status' => 'inherit',
    );

    $attachment_id = wp_insert_attachment($attachment, $file_path);

    if (is_wp_error($attachment_id) || !$attachment_id) {
      return new WP_Error('fcrs_attachment', 'Nao foi possivel criar o item na biblioteca de midia.');
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $metadata);

    return (int) $attachment_id;
  }

  /**
   * @param string $mime_type
   * @return string
   */
  private function mime_to_extension($mime_type) {
    $map = array(
      'image/png' => 'png',
      'image/jpeg' => 'jpg',
      'image/webp' => 'webp',
      'image/gif' => 'gif',
    );

    return isset($map[$mime_type]) ? $map[$mime_type] : 'png';
  }

  /**
   * @return array<string, mixed>|null
   */
  private function get_result() {
    if (empty($_GET['fcrs_result'])) {
      return null;
    }

    $token = sanitize_text_field(wp_unslash($_GET['fcrs_result']));

    if ('' === $token) {
      return null;
    }

    $result = get_transient(self::RESULT_PREFIX . $token);

    if (!is_array($result)) {
      return null;
    }

    return $result;
  }

  /**
   * @return string
   */
  private function get_result_token() {
    if (empty($_GET['fcrs_result'])) {
      return '';
    }

    return sanitize_text_field(wp_unslash($_GET['fcrs_result']));
  }

  /**
   * @param string $token
   * @return array<string, mixed>|null
   */
  private function get_result_by_token($token) {
    if ('' === $token) {
      return null;
    }

    $result = get_transient(self::RESULT_PREFIX . $token);

    return is_array($result) ? $result : null;
  }

  /**
   * @param string $token
   * @return string
   */
  private function get_result_download_url($token) {
    if ('' === $token) {
      return '';
    }

    return add_query_arg(
      array(
        'action' => self::DOWNLOAD_ACTION,
        'resultToken' => $token,
      ),
      admin_url('admin-post.php')
    );
  }

  /**
   * @param string $token
   * @param array<string, mixed> $result
   * @return void
   */
  private function store_result_by_token($token, array $result) {
    if ('' === $token) {
      return;
    }

    set_transient(self::RESULT_PREFIX . $token, $result, self::RESULT_TTL);
  }

  /**
   * @param string $redirect_url
   * @param array<string, mixed> $result
   * @param string $template_key
   * @param string $model
   * @param string $generation_type
   * @param int $save_to_media
   * @return void
   */
  private function redirect_with_result($redirect_url, array $result, $template_key, $model, $generation_type, $save_to_media) {
    if (isset($_POST['manualPrompt']) && !isset($result['input_prompt'])) {
      $result['input_prompt'] = sanitize_textarea_field((string) wp_unslash($_POST['manualPrompt']));
    }

    if (!isset($result['input_image_options'])) {
      $result['input_image_options'] = $this->merge_image_options(
        array(),
        $this->sanitize_image_options($_POST, 'fcrs_test_', false)
      );
    }

    if (isset($_POST['templateParameterValue']) && !isset($result['input_parameter_value'])) {
      $result['input_parameter_value'] = sanitize_text_field((string) wp_unslash($_POST['templateParameterValue']));
    }

    if (!isset($result['template_key'])) {
      $result['template_key'] = sanitize_key((string) $template_key);
    }

    if (!isset($result['generation_context'])) {
      $result['generation_context'] = isset($_POST['generationContext'])
        ? $this->normalize_generation_context(sanitize_key(wp_unslash($_POST['generationContext'])))
        : 'admin';
    }

    $token = wp_generate_password(20, false, false);
    $this->store_result_by_token($token, $result);

    $redirect_url = add_query_arg(
      array(
        'fcrs_result' => $token,
        'fcrs_template' => $template_key,
        'fcrs_model' => $model,
        'fcrs_generation_type' => $generation_type,
        'fcrs_save_media' => $save_to_media ? '1' : '0',
      ),
      $redirect_url
    );

    wp_safe_redirect($redirect_url);
    exit;
  }

  /**
   * @return string
   */
  private function get_redirect_url() {
    $fallback = home_url('/');
    $requested = isset($_POST['redirect_to'])
      ? wp_unslash($_POST['redirect_to'])
      : '';

    if (!$requested) {
      $requested = wp_get_referer();
    }

    if (!$requested) {
      return $fallback;
    }

    return wp_validate_redirect($requested, $fallback);
  }

  /**
   * @return string
   */
  private function get_current_url() {
    $fallback = home_url('/');
    $request_uri = isset($_SERVER['REQUEST_URI'])
      ? wp_unslash($_SERVER['REQUEST_URI'])
      : '';

    if (!$request_uri) {
      return $fallback;
    }

    return home_url($request_uri);
  }

  /**
   * @param string $url
   * @return string
   */
  private function get_generation_reset_url($url) {
    $url = (string) $url;

    if ('' === $url) {
      return home_url('/');
    }

    $parts = wp_parse_url($url);

    if (empty($parts['scheme']) || empty($parts['host'])) {
      return $url;
    }

    $query_args = array();

    if (!empty($parts['query'])) {
      wp_parse_str((string) $parts['query'], $query_args);
    }

    foreach (array_keys($query_args) as $query_key) {
      if (0 === strpos((string) $query_key, 'fcrs_')) {
        unset($query_args[$query_key]);
      }
    }

    $base_url = $parts['scheme'] . '://' . $parts['host'];

    if (!empty($parts['port'])) {
      $base_url .= ':' . $parts['port'];
    }

    if (!empty($parts['path'])) {
      $base_url .= $parts['path'];
    }

    $rebuilt_url = !empty($query_args)
      ? add_query_arg($query_args, $base_url)
      : $base_url;

    if (!empty($parts['fragment'])) {
      $rebuilt_url .= '#' . $parts['fragment'];
    }

    return $rebuilt_url;
  }

  /**
   * @param string $file_path
   * @param string $mime_type
   * @param string $filename
   * @return void
   */
  private function stream_local_download($file_path, $mime_type, $filename) {
    nocache_headers();
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
  }

  /**
   * @param string $remote_url
   * @param string $mime_type
   * @param string $filename
   * @return void
   */
  private function stream_remote_download($remote_url, $mime_type, $filename) {
    $temp_file = wp_tempnam('fcrs-download-');

    if (!$temp_file) {
      wp_die('Nao foi possivel preparar o download.', 500);
    }

    $response = wp_remote_get(
      $remote_url,
      array(
        'timeout' => 120,
        'stream' => true,
        'filename' => $temp_file,
      )
    );

    if (is_wp_error($response)) {
      @unlink($temp_file);
      wp_die('Falha ao baixar o arquivo final.', 502);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 300 || !file_exists($temp_file)) {
      @unlink($temp_file);
      wp_die('O arquivo final nao pode ser baixado agora.', 502);
    }

    nocache_headers();
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('Content-Length: ' . filesize($temp_file));
    readfile($temp_file);
    @unlink($temp_file);
    exit;
  }

  /**
   * @param string $url
   * @return string
   */
  private function get_local_file_path_from_url($url) {
    if ('' === $url) {
      return '';
    }

    $uploads = wp_upload_dir();
    $base_url = !empty($uploads['baseurl']) ? trailingslashit((string) $uploads['baseurl']) : '';
    $base_dir = !empty($uploads['basedir']) ? trailingslashit((string) $uploads['basedir']) : '';

    if ('' === $base_url || '' === $base_dir || 0 !== strpos($url, $base_url)) {
      return '';
    }

    $relative_path = ltrim(substr($url, strlen($base_url)), '/');

    return $base_dir . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
  }

  private function enqueue_assets() {
    wp_enqueue_style(
      'fcrs-front-v3',
      FCRS_PLUGIN_URL . 'assets/fcrs-front-v3.css',
      array(),
      '1.8.35'
    );
    wp_enqueue_script(
      'fcrs-ui-v3',
      FCRS_PLUGIN_URL . 'assets/fcrs-ui-v3.js',
      array(),
      '1.8.35',
      true
    );
    wp_localize_script(
      'fcrs-ui-v3',
      'FCRSUi',
      array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'statusAction' => self::JOB_STATUS_ACTION,
        'statusNonce' => wp_create_nonce(self::STATUS_NONCE),
        'pollInitialDelayMs' => 1500,
        'pollScheduleMs' => array(6000, 10000, 15000, 20000),
        'pollHiddenTabMs' => 30000,
        'pollErrorDelayMs' => 15000,
        'maxReferenceImages' => self::MAX_REFERENCE_IMAGES,
        'loadingMessages' => array(
          'Estamos gerando a sua imagem com muito carinho.',
          'Organizando luz, enquadramento e detalhes finais.',
          'Escolhendo os melhores detalhes para deixar tudo ainda mais bonito.',
          'Preparando um resultado caprichado para aparecer aqui.',
          'Ajustando cores, contraste e expressão com bastante cuidado.',
          'Quase lá: sua imagem está recebendo os últimos retoques.',
          'Dando um toque especial para a sua imagem ficar ainda mais incrível.',
          'Refinando cada detalhe para entregar um resultado bem bonito.',
        ),
      )
    );
  }

  /**
   * @param string $template_key
   * @param string $model_override
   * @param array<string, string> $args
   * @return string
   */
  private function render_generator_panel($template_key, $model_override, array $args) {
    $template = $this->get_template($template_key);

    if (!$template) {
      return '';
    }

    $this->enqueue_assets();

    $result = $this->get_result();
    $result_token = $this->get_result_token();

    if (
      $result
      && !empty($result['template_key'])
      && sanitize_key((string) $result['template_key']) !== sanitize_key((string) $template_key)
    ) {
      $result = null;
      $result_token = '';
    }

    $config = $this->get_config();
    $action_url = esc_url(admin_url('admin-post.php'));
    $clean_current_url = $this->get_generation_reset_url($this->get_current_url());
    $redirect_to = esc_url($clean_current_url);
    $title = !empty($args['title']) ? (string) $args['title'] : 'Gerador de imagem';
    $button_disabled = !$config['ready'];
    $model_in_use = $model_override
      ? $model_override
      : $template['default_model'];
    $template_requires_reference = !empty($template['require_reference']);
    $template_use_rewarded = !empty($template['use_rewarded']);
    $template_ui_language = !empty($template['ui_language'])
      ? $this->normalize_interface_language((string) $template['ui_language'])
      : 'pt_BR';
    $ui_strings = $this->get_interface_strings($template_ui_language);
    $submit_label = !empty($ui_strings['generate_label']) ? (string) $ui_strings['generate_label'] : 'Gerar imagem';
    $generator_description = !empty($args['description'])
      ? (string) $args['description']
      : (!empty($template['use_text_parameter']) ? (string) $ui_strings['text_parameter_tip'] : (string) $ui_strings['tip']);
    $template_generation_count = isset($result['template_generation_count'])
      ? max(0, (int) $result['template_generation_count'])
      : $this->get_template_generation_count($template_key);
    $template_generation_count_label = $this->format_template_generation_count_label($ui_strings, $template_generation_count);
    $max_reference_images = $this->get_template_max_reference_images($template);
    $template_has_reference_image = $this->template_uses_reference_image($template);
    $template_text_parameter = $this->get_template_text_parameter($template);
    $template_uses_text_parameter = !empty($template_text_parameter['enabled']);
    $input_parameter_value = isset($result['input_parameter_value'])
      ? (string) $result['input_parameter_value']
      : '';
    $template_color_palette = $this->get_template_color_palette(!empty($template['template_color']) ? (string) $template['template_color'] : 'blue');
    $template_color_style = $this->build_template_color_style($template_color_palette);
    $download_action_url = ($result_token && $result && !empty($result['download_url']))
      ? esc_url($this->get_result_download_url($result_token))
      : '';
    $reset_url = esc_url($clean_current_url);
    $article_config = array(
      'template_key' => $template_key,
    );

    ob_start();
    include FCRS_PLUGIN_DIR . 'templates/article-generator-v3.php';
    return (string) ob_get_clean();
  }

  /**
   * @param string $candidate
   * @param array<string, array<string, mixed>> $templates
   * @param string $current_key
   * @return string
   */
  private function get_unique_template_key($candidate, array $templates, $current_key = '') {
    $base_key = sanitize_key($candidate);

    if ('' === $base_key) {
      $base_key = 'template';
    }

    if ('' !== $current_key && $current_key === $base_key) {
      return $base_key;
    }

    $key = $base_key;
    $suffix = 2;

    while (isset($templates[$key]) && $key !== $current_key) {
      $key = $base_key . '_' . $suffix;
      $suffix++;
    }

    return $key;
  }

  /**
   * @param string $template_key
   * @return int
   */
  private function count_template_usage($template_key) {
    global $wpdb;

    $query = new WP_Query(
      array(
        'post_type' => array('post', 'page'),
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => self::POST_META_TEMPLATE,
        'meta_value' => $template_key,
        'no_found_rows' => false,
      )
    );

    $meta_count = (int) $query->found_posts;
    $block_signature = '"templateKey":"' . $template_key . '"';
    $block_count = (int) $wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(ID)
         FROM {$wpdb->posts}
         WHERE post_type IN ('post', 'page')
           AND post_status NOT IN ('auto-draft', 'trash')
           AND post_content LIKE %s",
        '%' . $wpdb->esc_like($block_signature) . '%'
      )
    );

    return $meta_count + $block_count;
  }

  /**
   * @param array<string, string> $args
   * @return void
   */
  private function redirect_admin_page(array $args = array()) {
    wp_safe_redirect(add_query_arg($args, admin_url('tools.php?page=' . self::PAGE_SLUG)));
    exit;
  }

  /**
   * @param string $endpoint
   * @param string $path
   * @return string
   */
  private function build_endpoint_url($endpoint, $path = '') {
    $base = rtrim((string) $endpoint, '/');
    $suffix = $path ? '/' . ltrim((string) $path, '/') : '';

    return $base . $suffix;
  }

  /**
   * @param array<string, mixed> $config
   * @param string $job_id
   * @param array<string, mixed> $status_data
   * @param array<string, mixed> $current_result
   * @return array<string, mixed>|WP_Error
   */
  private function resolve_completed_job_result(array $config, $job_id, array $status_data, array $current_result) {
    $mime_type = !empty($status_data['mimeType'])
      ? (string) $status_data['mimeType']
      : 'image/png';
    $remote_result_url = !empty($status_data['resultUrl'])
      ? esc_url_raw((string) $status_data['resultUrl'])
      : '';

    if ($remote_result_url && empty($current_result['save_to_media'])) {
      $resolved_result = $current_result;
      $resolved_result['status'] = 'success';
      $resolved_result['message'] = 'Imagem gerada com sucesso.';
      $resolved_result['preview_url'] = $remote_result_url;
      $resolved_result['download_url'] = $remote_result_url;
      $resolved_result['mime_type'] = $mime_type;
      $resolved_result['text'] = !empty($status_data['text'])
        ? wp_strip_all_tags((string) $status_data['text'])
        : '';
      $resolved_result['remote_result'] = 1;
      $resolved_result['temporary_result'] = !empty($status_data['temporaryResult']) ? 1 : 0;
      $resolved_result['result_url_expires_at'] = !empty($status_data['resultUrlExpiresAt'])
        ? (string) $status_data['resultUrlExpiresAt']
        : '';

      return $resolved_result;
    }

    $temp_file = wp_tempnam('fcrs-job-' . $job_id);

    if (!$temp_file) {
      return new WP_Error('fcrs_temp_file', 'Nao foi possivel preparar um arquivo temporario para baixar o resultado.');
    }

    $download_response = wp_remote_get(
      $this->build_endpoint_url($config['endpoint'], '/jobs/' . rawurlencode($job_id) . '/result'),
      array(
        'timeout' => max(60, $config['timeout']),
        'headers' => array(
          'X-Site-Secret' => $config['secret'],
        ),
        'stream' => true,
        'filename' => $temp_file,
      )
    );

    if (is_wp_error($download_response)) {
      @unlink($temp_file);
      return new WP_Error('fcrs_download_job', 'Falha ao baixar o resultado do job: ' . $download_response->get_error_message());
    }

    $status_code = (int) wp_remote_retrieve_response_code($download_response);

    if ($status_code < 200 || $status_code >= 300) {
      @unlink($temp_file);
      return new WP_Error('fcrs_download_job_status', 'O endpoint nao devolveu o arquivo final do job.');
    }

    $mime_type = !empty($status_data['mimeType'])
      ? (string) $status_data['mimeType']
      : (string) wp_remote_retrieve_header($download_response, 'content-type');

    $preview = $this->save_preview_file_from_path($temp_file, $mime_type);
    @unlink($temp_file);

    if (is_wp_error($preview)) {
      return $preview;
    }

    $resolved_result = $current_result;
    $resolved_result['status'] = 'success';
    $resolved_result['message'] = 'Imagem gerada com sucesso.';
    $resolved_result['preview_url'] = $preview['url'];
    $resolved_result['download_url'] = $preview['url'];
    $resolved_result['mime_type'] = $mime_type;
    $resolved_result['text'] = !empty($status_data['text'])
      ? wp_strip_all_tags((string) $status_data['text'])
      : '';
    $resolved_result['remote_result'] = 0;
    $resolved_result['temporary_result'] = !empty($status_data['temporaryResult']) ? 1 : 0;
    $resolved_result['result_url_expires_at'] = !empty($status_data['resultUrlExpiresAt'])
      ? (string) $status_data['resultUrlExpiresAt']
      : '';

    if (!empty($resolved_result['save_to_media'])) {
      $attachment_id = $this->create_media_attachment($preview['file'], $preview['url'], $mime_type);

      if (is_wp_error($attachment_id)) {
        $resolved_result['status'] = 'warning';
        $resolved_result['message'] = 'A imagem foi gerada, mas nao foi possivel salvar na biblioteca de midia.';
        $resolved_result['text'] = trim($resolved_result['text'] . ' ' . $attachment_id->get_error_message());
      } else {
        $resolved_result['attachment_id'] = $attachment_id;
      }
    }

    return $resolved_result;
  }

  /**
   * @param array<string, mixed> $source
   * @param string $prefix
   * @param bool $with_defaults
   * @return array<string, mixed>
   */
  private function sanitize_image_options(array $source, $prefix = '', $with_defaults = true) {
    $aspect_ratio_options = $this->get_aspect_ratio_options();
    $image_size_options = $this->get_image_size_options();
    $format_options = $this->get_format_options();
    $aspect_ratio_key = $prefix ? $prefix . 'aspect_ratio' : 'aspectRatio';
    $image_size_key = $prefix ? $prefix . 'image_size' : 'imageSize';
    $output_mime_key = $prefix ? $prefix . 'output_mime_type' : 'outputMimeType';
    $compression_key = $prefix ? $prefix . 'compression_quality' : 'compressionQuality';
    $options = array();

    if (isset($source[$aspect_ratio_key])) {
      $aspect_ratio = sanitize_text_field((string) wp_unslash($source[$aspect_ratio_key]));

      if (isset($aspect_ratio_options[$aspect_ratio])) {
        $options['aspectRatio'] = $aspect_ratio;
      }
    }

    if (isset($source[$image_size_key])) {
      $image_size = strtoupper(sanitize_text_field((string) wp_unslash($source[$image_size_key])));

      if (isset($image_size_options[$image_size])) {
        $options['imageSize'] = $image_size;
      }
    }

    if (isset($source[$output_mime_key])) {
      $output_mime_type = sanitize_text_field((string) wp_unslash($source[$output_mime_key]));

      if (isset($format_options[$output_mime_type])) {
        $options['outputMimeType'] = $output_mime_type;
      }
    }

    if (isset($source[$compression_key])) {
      $options['compressionQuality'] = min(100, max(0, (int) wp_unslash($source[$compression_key])));
    }

    if (!$with_defaults) {
      return $options;
    }

    return array(
      'aspectRatio' => !empty($options['aspectRatio']) ? $options['aspectRatio'] : self::DEFAULT_ASPECT_RATIO,
      'imageSize' => !empty($options['imageSize']) ? $options['imageSize'] : self::DEFAULT_IMAGE_SIZE,
      'outputMimeType' => !empty($options['outputMimeType']) ? $options['outputMimeType'] : self::DEFAULT_IMAGE_FORMAT,
      'compressionQuality' => isset($options['compressionQuality']) ? $options['compressionQuality'] : self::DEFAULT_JPEG_QUALITY,
    );
  }

  /**
   * @param array<string, mixed> $template
   * @return array<string, mixed>
   */
  private function extract_template_image_options(array $template) {
    if (empty($template['image_options']) || !is_array($template['image_options'])) {
      return $this->get_default_image_options();
    }

    return $this->sanitize_image_options($template['image_options'], '', true);
  }

  /**
   * @param array<string, mixed> $base
   * @param array<string, mixed> $override
   * @return array<string, mixed>
   */
  private function merge_image_options(array $base, array $override) {
    return $this->sanitize_image_options(array_merge($base, $override), '', true);
  }

  /**
   * @return array<string, mixed>
   */
  private function get_default_image_options() {
    return array(
      'aspectRatio' => self::DEFAULT_ASPECT_RATIO,
      'imageSize' => self::DEFAULT_IMAGE_SIZE,
      'outputMimeType' => self::DEFAULT_IMAGE_FORMAT,
      'compressionQuality' => self::DEFAULT_JPEG_QUALITY,
    );
  }
}
