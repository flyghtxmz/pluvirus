<?php

defined('ABSPATH') || exit;

class FCRS_Plugin {
  const ACTION = 'fcrs_generate_image';
  const NONCE = 'fcrs_generate_nonce';
  const SHORTCODE = 'felipe_cloud_run_studio';
  const RESULT_PREFIX = 'fcrs_result_';
  const PAGE_SLUG = 'felipe-cloud-run-studio';

  /**
   * @var FCRS_Plugin|null
   */
  private static $instance = null;

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
    add_action('admin_post_' . self::ACTION, array($this, 'handle_request'));
    add_action('admin_post_nopriv_' . self::ACTION, array($this, 'handle_request'));
    add_action('admin_menu', array($this, 'register_admin_page'));
  }

  public function register_admin_page() {
    add_management_page(
      'Felipe Cloud Run Studio',
      'Felipe Cloud Run Studio',
      'manage_options',
      self::PAGE_SLUG,
      array($this, 'render_admin_page')
    );
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

    return $this->render_panel(
      array(
        'title' => (string) $atts['title'],
        'submit_label' => (string) $atts['submit_label'],
        'description' => 'Escreva o prompt, envie uma imagem de referencia se quiser e gere a imagem pelo backend do WordPress.',
        'redirect_to' => $this->get_current_url(),
        'show_config_box' => false,
      )
    );
  }

  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      return;
    }

    echo '<div class="wrap">';
    echo '<h1>Felipe Cloud Run Studio</h1>';
    echo $this->render_panel(
      array(
        'title' => 'Painel de teste',
        'submit_label' => 'Testar endpoint e gerar',
        'description' => 'Esta tela chama o seu endpoint real do Cloud Run usando o backend do WordPress.',
        'redirect_to' => admin_url('tools.php?page=' . self::PAGE_SLUG),
        'show_config_box' => true,
      )
    );
    echo '</div>';
  }

  /**
   * @param array<string, mixed> $args
   * @return string
   */
  private function render_panel(array $args) {
    $this->enqueue_assets();

    $config = $this->get_config();
    $templates = $this->get_templates();
    $result = $this->get_result();
    $selected_template = isset($_GET['fcrs_template'])
      ? sanitize_key(wp_unslash($_GET['fcrs_template']))
      : 'product_ad';
    $user_prompt = isset($_GET['fcrs_prompt'])
      ? sanitize_textarea_field(wp_unslash($_GET['fcrs_prompt']))
      : '';
    $save_to_media = !empty($_GET['fcrs_save_media']);
    $action_url = esc_url(admin_url('admin-post.php'));
    $redirect_to = esc_url((string) $args['redirect_to']);
    $title = (string) $args['title'];
    $submit_label = (string) $args['submit_label'];
    $description = (string) $args['description'];
    $show_config_box = !empty($args['show_config_box']);

    ob_start();
    include FCRS_PLUGIN_DIR . 'templates/panel.php';
    return (string) ob_get_clean();
  }

  public function handle_request() {
    check_admin_referer(self::NONCE, 'fcrs_nonce');

    $redirect_url = $this->get_redirect_url();
    $template = isset($_POST['template']) ? sanitize_key(wp_unslash($_POST['template'])) : '';
    $user_prompt = isset($_POST['userPrompt'])
      ? sanitize_textarea_field(wp_unslash($_POST['userPrompt']))
      : '';
    $save_to_media = !empty($_POST['saveToMedia']) ? 1 : 0;
    $config = $this->get_config();

    if (!$config['ready']) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Configure endpoint e chave no wp-config.php antes de usar o plugin.',
        ),
        $template,
        $user_prompt,
        $save_to_media
      );
    }

    $templates = $this->get_templates();

    if (!array_key_exists($template, $templates)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'Template invalido.',
        ),
        $template,
        $user_prompt,
        $save_to_media
      );
    }

    $payload = array(
      'template' => $template,
    );

    if ('' !== $user_prompt) {
      $payload['userPrompt'] = $user_prompt;
    }

    if (!empty($config['model'])) {
      $payload['model'] = $config['model'];
    }

    $reference_image = $this->read_reference_image();

    if (is_wp_error($reference_image)) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => $reference_image->get_error_message(),
        ),
        $template,
        $user_prompt,
        $save_to_media
      );
    }

    if (!empty($reference_image)) {
      $payload['imageBase64'] = $reference_image['imageBase64'];
      $payload['mimeType'] = $reference_image['mimeType'];
    }

    $response = wp_remote_post(
      $config['endpoint'],
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
          'message' => 'Falha ao chamar o Cloud Run: ' . $response->get_error_message(),
        ),
        $template,
        $user_prompt,
        $save_to_media
      );
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status_code < 200 || $status_code >= 300) {
      $message = is_array($data) && !empty($data['error'])
        ? (string) $data['error']
        : 'Cloud Run respondeu com erro.';
      $details = is_array($data) && !empty($data['details'])
        ? ' ' . (string) $data['details']
        : '';

      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => trim($message . $details),
        ),
        $template,
        $user_prompt,
        $save_to_media
      );
    }

    if (!is_array($data) || empty($data['imageBase64']) || empty($data['mimeType'])) {
      $this->redirect_with_result(
        $redirect_url,
        array(
          'status' => 'error',
          'message' => 'O Cloud Run nao devolveu uma imagem valida.',
        ),
        $template,
        $user_prompt,
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
        $template,
        $user_prompt,
        $save_to_media
      );
    }

    $result = array(
      'status' => 'success',
      'message' => 'Imagem gerada com sucesso.',
      'preview_url' => $preview['url'],
      'mime_type' => (string) $data['mimeType'],
      'text' => !empty($data['text']) ? wp_strip_all_tags((string) $data['text']) : '',
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

    $this->redirect_with_result($redirect_url, $result, $template, $user_prompt, $save_to_media);
  }

  /**
   * @return array<string, mixed>
   */
  private function get_config() {
    $endpoint = '';
    $secret = '';
    $timeout = 120;
    $model = '';

    if (defined('FCRS_CLOUD_RUN_ENDPOINT') && FCRS_CLOUD_RUN_ENDPOINT) {
      $endpoint = (string) FCRS_CLOUD_RUN_ENDPOINT;
    } elseif (defined('CLOUD_RUN_ENDPOINT') && CLOUD_RUN_ENDPOINT) {
      $endpoint = (string) CLOUD_RUN_ENDPOINT;
    }

    if (defined('FCRS_CLOUD_RUN_SHARED_SECRET') && FCRS_CLOUD_RUN_SHARED_SECRET) {
      $secret = (string) FCRS_CLOUD_RUN_SHARED_SECRET;
    } elseif (defined('CLOUD_RUN_SHARED_SECRET') && CLOUD_RUN_SHARED_SECRET) {
      $secret = (string) CLOUD_RUN_SHARED_SECRET;
    }

    if (defined('FCRS_CLOUD_RUN_TIMEOUT') && FCRS_CLOUD_RUN_TIMEOUT) {
      $timeout = max(30, (int) FCRS_CLOUD_RUN_TIMEOUT);
    } elseif (defined('CLOUD_RUN_TIMEOUT') && CLOUD_RUN_TIMEOUT) {
      $timeout = max(30, (int) CLOUD_RUN_TIMEOUT);
    }

    if (defined('FCRS_CLOUD_RUN_MODEL') && FCRS_CLOUD_RUN_MODEL) {
      $model = (string) FCRS_CLOUD_RUN_MODEL;
    } elseif (defined('CLOUD_RUN_MODEL') && CLOUD_RUN_MODEL) {
      $model = (string) CLOUD_RUN_MODEL;
    }

    return array(
      'endpoint' => $endpoint,
      'secret' => $secret,
      'timeout' => $timeout,
      'model' => $model,
      'ready' => '' !== $endpoint && '' !== $secret,
    );
  }

  /**
   * @return array<string, string>
   */
  private function get_templates() {
    $templates = array(
      'product_ad' => 'Product Ad',
      'character_portrait' => 'Character Portrait',
      'social_media' => 'Social Media',
    );

    return apply_filters('fcrs_templates', $templates);
  }

  /**
   * @return array<string, mixed>|WP_Error
   */
  private function read_reference_image() {
    if (empty($_FILES['referenceImage']) || !is_array($_FILES['referenceImage'])) {
      return array();
    }

    $file = $_FILES['referenceImage'];

    if (!isset($file['error']) || UPLOAD_ERR_NO_FILE === (int) $file['error']) {
      return array();
    }

    if (UPLOAD_ERR_OK !== (int) $file['error']) {
      return new WP_Error('fcrs_upload_error', 'Falha no envio da imagem de referencia.');
    }

    $tmp_name = !empty($file['tmp_name']) ? $file['tmp_name'] : '';
    $size = !empty($file['size']) ? (int) $file['size'] : 0;

    if (!$tmp_name || $size <= 0) {
      return new WP_Error('fcrs_upload_invalid', 'Arquivo de imagem invalido.');
    }

    if ($size > 10 * MB_IN_BYTES) {
      return new WP_Error('fcrs_upload_size', 'A imagem de referencia precisa ter no maximo 10 MB.');
    }

    $mime_type = function_exists('mime_content_type')
      ? mime_content_type($tmp_name)
      : '';

    if (!$mime_type || 0 !== strpos($mime_type, 'image/')) {
      return new WP_Error('fcrs_upload_type', 'Envie apenas arquivos de imagem.');
    }

    $binary = file_get_contents($tmp_name);

    if (false === $binary) {
      return new WP_Error('fcrs_upload_read', 'Nao foi possivel ler a imagem de referencia.');
    }

    return array(
      'imageBase64' => base64_encode($binary),
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
      return new WP_Error('fcrs_invalid_base64', 'A imagem retornada pelo Cloud Run veio em base64 invalido.');
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
   * @param string $file_path
   * @param string $file_url
   * @param string $mime_type
   * @return int|WP_Error
   */
  private function create_media_attachment($file_path, $file_url, $mime_type) {
    $attachment = array(
      'guid' => $file_url,
      'post_mime_type' => $mime_type,
      'post_title' => preg_replace('/\.[^.]+$/', '', wp_basename($file_path)),
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
   * @param string $redirect_url
   * @param array<string, mixed> $result
   * @param string $template
   * @param string $user_prompt
   * @param int $save_to_media
   * @return void
   */
  private function redirect_with_result($redirect_url, array $result, $template, $user_prompt, $save_to_media) {
    $token = wp_generate_password(20, false, false);
    set_transient(self::RESULT_PREFIX . $token, $result, 15 * MINUTE_IN_SECONDS);

    $redirect_url = add_query_arg(
      array(
        'fcrs_result' => $token,
        'fcrs_template' => $template,
        'fcrs_prompt' => $user_prompt,
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

  private function enqueue_assets() {
    wp_enqueue_script(
      'fcrs-preview',
      FCRS_PLUGIN_URL . 'assets/fcrs-preview.js',
      array(),
      '1.0.0',
      true
    );
  }
}
