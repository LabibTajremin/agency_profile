<?php

/**
 * Signatures for the WordPress functions this plugin calls.
 *
 * PHPStan needs to know these exist and what they return. The alternative — pulling the whole
 * of WordPress into static analysis — costs minutes per run and drowns real findings in
 * core's own noise. This file is scanned, never executed.
 *
 * @package Edulume\Core
 */

declare(strict_types=1);

/**
 * @param mixed $default
 *
 * @return mixed
 */
function get_option(string $option, $default = false)
{
}

/**
 * @param mixed $value
 */
function update_option(string $option, $value, ?bool $autoload = null): bool
{
}

function delete_option(string $option): bool
{
}

function get_role(string $role): ?WP_Role
{
}

function wp_roles(): WP_Roles
{
}

/**
 * @param callable $callback
 */
function add_action(string $hook, $callback, int $priority = 10, int $acceptedArgs = 1): bool
{
}

/**
 * @param callable $callback
 */
function add_filter(string $hook, $callback, int $priority = 10, int $acceptedArgs = 1): bool
{
}

/**
 * @param callable $callback
 */
function register_activation_hook(string $file, $callback): void
{
}

/**
 * @param callable $callback
 */
function register_deactivation_hook(string $file, $callback): void
{
}

function load_plugin_textdomain(string $domain, bool $deprecated = false, string $pluginRelPath = ''): bool
{
}

function plugin_basename(string $file): string
{
}

function plugins_url(string $path = '', string $plugin = ''): string
{
}

function esc_textarea(string $text): string
{
}

function esc_url_raw(string $url): string
{
}

/**
 * @param string|array{0: int, 1: int} $size
 * @return string|false
 */
function wp_get_attachment_image_url(int $attachmentId, $size = 'thumbnail', bool $icon = false)
{
}

function sanitize_textarea_field(string $value): string
{
}

function is_multisite(): bool
{
}

function is_user_logged_in(): bool
{
}

function status_header(int $code, string $description = ''): void
{
}

function nocache_headers(): void
{
}

function get_404_template(): string
{
}

/**
 * @return string|array<string, mixed>|int|null
 */
function wp_parse_url(string $url, int $component = -1)
{
}

/**
 * @param array<string, mixed> $args
 * @return array<string, object>
 */
function get_post_types(array $args = [], string $output = 'names', string $operator = 'and'): array
{
}

/**
 * @param array<string, mixed> $args
 * @return array<string, object>
 */
function get_taxonomies(array $args = [], string $output = 'names', string $operator = 'and'): array
{
}

function flush_rewrite_rules(bool $hard = true): void
{
}

function wp_mkdir_p(string $target): bool
{
}

/**
 * @return array{path:string, url:string, basedir:string, baseurl:string, error:string|false}
 */
function wp_get_upload_dir(): array
{
}

function wp_delete_file(string $file): void
{
}

/**
 * @param list<string> $deps
 * @param string|bool|null $ver
 */
function wp_enqueue_script(string $handle, string $src = '', array $deps = [], $ver = false, bool $inFooter = false): bool
{
}

function wp_enqueue_style(string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all'): bool
{
}

/**
 * @param array<string, mixed> $args
 */
function register_post_type(string $postType, array $args = []): void
{
}

/**
 * @param string|list<string> $objectType
 * @param array<string, mixed> $args
 */
function register_taxonomy(string $taxonomy, $objectType, array $args = []): void
{
}

/**
 * @param array<string, mixed> $args
 */
function register_post_meta(string $postType, string $metaKey, array $args = []): bool
{
}

/**
 * @param mixed $value
 * @param mixed ...$args
 *
 * @return mixed
 */
function apply_filters(string $hook, $value, ...$args)
{
}

/**
 * @param int|string|null $object
 */
function current_user_can(string $capability, ...$args): bool
{
}

/**
 * @param string $queries
 *
 * @return array<string, string>
 */
function dbDelta($queries = '', bool $execute = true): array
{
}

const ABSPATH = '/';

class wpdb
{
    public string $prefix;

    public string $options;

    public string $posts;

    /**
     * @param mixed ...$args
     */
    public function prepare(string $query, ...$args): string
    {
    }

    /**
     * @param list<mixed>|null $args
     *
     * @return string|null
     */
    public function get_var(?string $query = null, int $x = 0, int $y = 0)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get_results(?string $query = null, string $output = 'OBJECT'): array
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>|null $format
     */
    public function insert(string $table, array $data, $format = null): int|false
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $data, array $where): int|false
    {
    }

    public function query(string $query): int|bool
    {
    }

    public function get_charset_collate(): string
    {
    }

    public int $insert_id;

    public function esc_like(string $text): string
    {
    }
}

/**
 * @param mixed $data
 *
 * @return string|false
 */
function wp_json_encode($data, int $flags = 0, int $depth = 512)
{
}

/**
 * @param array<string, mixed> $args
 */
function register_rest_route(string $namespace, string $route, array $args = [], bool $override = false): bool
{
}

class WP_Role
{
    public string $name;

    /** @var array<string, bool> */
    public array $capabilities;

    public function add_cap(string $cap, bool $grant = true): void
    {
    }

    public function remove_cap(string $cap): void
    {
    }

    public function has_cap(string $cap): bool
    {
    }
}

/**
 * @param array<string, mixed> $args
 */
function register_post_type(string $postType, array $args = []): void
{
}

/**
 * @param string|list<string> $objectType
 * @param array<string, mixed> $args
 */
function register_taxonomy(string $taxonomy, $objectType, array $args = []): void
{
}

/**
 * @param array<string, mixed> $args
 */
function register_post_meta(string $postType, string $metaKey, array $args = []): bool
{
}

/**
 * @param mixed $value
 * @param mixed ...$args
 *
 * @return mixed
 */
function apply_filters(string $hook, $value, ...$args)
{
}

/**
 * @param int|string|null $object
 */
function current_user_can(string $capability, ...$args): bool
{
}

/**
 * @param string $queries
 *
 * @return array<string, string>
 */
function dbDelta($queries = '', bool $execute = true): array
{
}

const ABSPATH = '/';

class wpdb
{
    public string $prefix;

    public string $options;

    public string $posts;

    /**
     * @param mixed ...$args
     */
    public function prepare(string $query, ...$args): string
    {
    }

    /**
     * @param list<mixed>|null $args
     *
     * @return string|null
     */
    public function get_var(?string $query = null, int $x = 0, int $y = 0)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function get_results(?string $query = null, string $output = 'OBJECT'): array
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>|null $format
     */
    public function insert(string $table, array $data, $format = null): int|false
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $data, array $where): int|false
    {
    }

    public function query(string $query): int|bool
    {
    }

    public function get_charset_collate(): string
    {
    }

    public int $insert_id;

    public function esc_like(string $text): string
    {
    }
}

/**
 * @param mixed $data
 *
 * @return string|false
 */
function wp_json_encode($data, int $flags = 0, int $depth = 512)
{
}

/**
 * @param array<string, mixed> $args
 */
function register_rest_route(string $namespace, string $route, array $args = [], bool $override = false): bool
{
}

class WP_Roles
{
    /** @var array<string, WP_Role> */
    public array $role_objects;

    /** @var array<string, string> */
    public array $role_names;
}

/**
 * @param array<string, mixed> $args
 *
 * @return mixed
 */
function register_block_type(string $name, array $args = [])
{
}

/**
 * @param array<string, mixed> $properties
 */
function register_block_style(string $blockName, array $properties): bool
{
}

/**
 * @param array<string, string> $attributes
 */
function get_block_wrapper_attributes(array $attributes = []): string
{
}

/**
 * @param mixed ...$args
 */
function do_action(string $hookName, ...$args): void
{
}

function esc_attr(string $text): string
{
}

/**
 * @param array<string, mixed> $postData
 *
 * @return int|WP_Error
 */
function wp_insert_post(array $postData, bool $wpError = false)
{
}

/**
 * @param mixed $value
 *
 * @return int|bool
 */
function update_post_meta(int $postId, string $key, $value)
{
}

/**
 * @param list<string>|string $terms
 *
 * @return array<int, int>|WP_Error
 */
function wp_set_object_terms(int $objectId, $terms, string $taxonomy, bool $append = false)
{
}

/**
 * @param array<string, mixed> $args
 *
 * @return array<int, mixed>
 */
function get_posts(array $args = []): array
{
}

class WP_Error
{
    /**
     * @param string|int $code
     * @param mixed $data
     */
    public function __construct($code = '', string $message = '', $data = '')
    {
    }

    public function get_error_message(string $code = ''): string
    {
    }

    /**
     * @return string|int
     */
    public function get_error_code()
    {
    }
}

/**
 * @param array<string, mixed> $args
 *
 * @return array<string, mixed>|WP_Error
 */
function wp_remote_post(string $url, array $args = [])
{
}

/**
 * @param array<string, mixed> $args
 *
 * @return array<string, mixed>|WP_Error
 */
function wp_remote_get(string $url, array $args = [])
{
}

/**
 * @param array<string, mixed>|WP_Error $response
 */
function wp_remote_retrieve_body($response): string
{
}

/**
 * @param array<string, mixed>|WP_Error $response
 *
 * @return int|string
 */
function wp_remote_retrieve_response_code($response)
{
}

/**
 * @param mixed $thing
 *
 * @phpstan-assert-if-true WP_Error $thing
 */
function is_wp_error($thing): bool
{
}

/**
 * @param mixed $value
 */
function set_transient(string $transient, $value, int $expiration = 0): bool
{
}

/**
 * @return mixed
 */
function get_transient(string $transient)
{
}

/**
 * @param mixed $data
 */
function wp_send_json_error($data = null, int $statusCode = 0): void
{
}

/**
 * @param mixed $data
 */
function wp_send_json_success($data = null, int $statusCode = 0): void
{
}

/**
 * @param int|string $action
 */
function check_ajax_referer($action = -1, $queryArg = false, bool $stopOnFail = true): bool
{
}

/**
 * @param int|string $action
 */
function wp_create_nonce($action = -1): string
{
}

/**
 * @param array<string, mixed> $l10n
 */
function wp_localize_script(string $handle, string $objectName, array $l10n): bool
{
}

function delete_transient(string $transient): bool
{
}

/**
 * @param string|list<string> $to
 * @param list<string>|string $headers
 * @param list<string> $attachments
 */
function wp_mail($to, string $subject, string $message, $headers = '', array $attachments = []): bool
{
}

function __(string $text, string $domain = 'default'): string
{
}

function _e(string $text, string $domain = 'default'): void
{
}

function _x(string $text, string $context, string $domain = 'default'): string
{
}

function _n(string $single, string $plural, int $number, string $domain = 'default'): string
{
}

function esc_html(string $text): string
{
}

function esc_html__(string $text, string $domain = 'default'): string
{
}

function esc_html_e(string $text, string $domain = 'default'): void
{
}

function esc_attr__(string $text, string $domain = 'default'): string
{
}

function esc_attr_e(string $text, string $domain = 'default'): void
{
}

function esc_url(string $url): string
{
}

function sanitize_text_field(string $value): string
{
}

function sanitize_key(string $key): string
{
}

function sanitize_email(string $email): string
{
}

/**
 * @param mixed $value
 *
 * @return mixed
 */
function wp_unslash($value)
{
}

/**
 * @return mixed
 */
function get_option(string $option, mixed $default = false)
{
}

/**
 * @param mixed $value
 */
function update_option(string $option, $value, ?bool $autoload = null): bool
{
}

function delete_option(string $option): bool
{
}

function home_url(string $path = ''): string
{
}

/**
 * @param mixed ...$args
 *
 * @return mixed
 */
function apply_filters(string $hookName, $value, ...$args)
{
}

/**
 * @param callable|string|array<int, mixed> $callback
 */
function add_action(string $hookName, $callback, int $priority = 10, int $acceptedArgs = 1): bool
{
}

/**
 * @param callable|string|array<int, mixed> $callback
 */
function add_filter(string $hookName, $callback, int $priority = 10, int $acceptedArgs = 1): bool
{
}

/**
 * @return array<string, mixed>
 */
function wp_upload_dir(?string $time = null, bool $createDir = true): array
{
}

function wp_delete_file(string $file): void
{
}

class WP_REST_Request
{
    /**
     * @return mixed
     */
    public function get_param(string $key)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function get_params(): array
    {
    }
}

function current_user_can(string $capability): bool
{
}

function get_current_user_id(): int
{
}

class WP_Post
{
    public int $ID;

    public string $post_name;

    public string $post_title;

    public string $post_type;
}

class WP_Query
{
    public function set_404(): void
    {
    }

    /** @var array<int, mixed> */
    public array $posts;

    public int $found_posts;

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
    }
}

/**
 * @param WP_Post|int|null $post
 */
function get_the_title($post = null): string
{
}

/**
 * @param WP_Post|int|null $post
 *
 * @return string|false
 */
function get_permalink($post = null)
{
}

/**
 * @param WP_Post|int|null $post
 */
function get_the_excerpt($post = null): string
{
}

/**
 * @param WP_Post|int|null $post
 *
 * @return string|false
 */
function get_the_post_thumbnail_url($post = null, string $size = 'post-thumbnail')
{
}

/**
 * @return array<string, int>|false
 */
function get_nav_menu_locations()
{
}

/**
 * @param int|string $menu
 *
 * @return object|false
 */
function wp_get_nav_menu_object($menu)
{
}

/**
 * @param mixed $value
 */
function set_theme_mod(string $name, $value): void
{
}

/**
 * @return WP_Post|null
 */
function get_page_by_path(string $path, string $output = 'OBJECT', string $postType = 'page')
{
}

function sanitize_title(string $title): string
{
}

/**
 * @return WP_Post|false|null
 */
function wp_delete_post(int $postId, bool $forceDelete = false)
{
}

const OBJECT = 'OBJECT';

class WP_CLI
{
    /**
     * @param object|callable|string $callable
     * @param array<string, mixed> $args
     */
    public static function add_command(string $name, $callable, array $args = []): bool
    {
    }

    public static function success(string $message): void
    {
    }

    public static function error(string $message): void
    {
    }

    public static function line(string $message = ''): void
    {
    }
}

function wp_print_inline_script_tag(string $javascript, array $attributes = []): void
{
}

function wp_strip_all_tags(string $text, bool $removeBreaks = false): string
{
}

/**
 * @return string|false
 */
function get_post_type_archive_link(string $postType)
{
}

/**
 * @param callable|string|array<int, mixed>|null $callback
 */
function add_menu_page(
    string $pageTitle,
    string $menuTitle,
    string $capability,
    string $menuSlug,
    $callback = '',
    string $iconUrl = '',
    ?int $position = null
): string {
}

/**
 * @param callable|string|array<int, mixed>|null $callback
 *
 * @return string|false
 */
function add_submenu_page(
    string $parentSlug,
    string $pageTitle,
    string $menuTitle,
    string $capability,
    string $menuSlug,
    $callback = '',
    ?int $position = null
) {
}

/**
 * Media library functions, used by the demo importer to sideload bundled artwork.
 *
 * @param array<string, mixed>|null $deprecated
 *
 * @return array{file?: string, url?: string, type?: string, error?: string|false}
 */
function wp_upload_bits(string $name, $deprecated, string $bits, ?string $time = null): array
{
}

/**
 * @param array<string, mixed>|string $attachment
 *
 * @return int|\WP_Error
 */
function wp_insert_attachment(
    $attachment,
    string $file = '',
    int $parentPostId = 0,
    bool $wpError = false,
    bool $fireAfterHooks = true
) {
}

/**
 * @return array<string, mixed>
 */
function wp_generate_attachment_metadata(int $attachmentId, string $file): array
{
}

/**
 * @param array<string, mixed> $data
 *
 * @return int|false
 */
function wp_update_attachment_metadata(int $attachmentId, array $data)
{
}

/**
 * @return int|bool
 */
function set_post_thumbnail(int $postId, int $thumbnailId)
{
}

/**
 * @return \WP_Post|false|null
 */
function wp_delete_attachment(int $postId, bool $forceDelete = false)
{
}

/**
 * @param int|\WP_Post|null $post
 *
 * @return string|false
 */
function get_post_type($post = null)
{
}

/**
 * Admin styling and screen helpers, used to paint wp-admin in the site's accent and to
 * register the contextual help each screen carries.
 *
 * @param string|false $src
 * @param list<string> $deps
 * @param string|bool|null $ver
 */
function wp_register_style(string $handle, $src, array $deps = [], $ver = false, string $media = 'all'): bool
{
}

function wp_add_inline_style(string $handle, string $data): bool
{
}

class WP_Screen
{
    public string $id = '';

    /**
     * @param array<string, mixed> $args
     */
    public function add_help_tab(array $args): void
    {
    }
}

/**
 * @return \WP_Screen|null
 */
function get_current_screen()
{
}

/**
 * Admin-post and redirect helpers, used by the starter-content screen.
 */
function admin_url(string $path = '', string $scheme = 'admin'): string
{
}

/**
 * @param array<string, mixed>|string $args
 */
function add_query_arg($args, string $url = ''): string
{
}

function wp_safe_redirect(string $location, int $status = 302, string $xRedirectBy = 'WordPress'): bool
{
}

/**
 * @param int|string $action
 *
 * @return int|false
 */
function check_admin_referer($action = -1, string $queryArg = '_wpnonce')
{
}

/**
 * @return string|void
 */
function wp_nonce_field(
    string $action = '-1',
    string $name = '_wpnonce',
    bool $referer = true,
    bool $display = true
) {
}

/**
 * @param string|\WP_Error $message
 * @param string|int $title
 * @param array<string, mixed>|int|string $args
 */
function wp_die($message = '', $title = '', $args = []): void
{
}

function sanitize_text_field(string $str): string
{
}

function _n(string $single, string $plural, int $number, string $domain = 'default'): string
{
}

function rawurlencode(string $str): string
{
}
