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
    public function get_error_message(string $code = ''): string
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
