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

class WP_Roles
{
    /** @var array<string, WP_Role> */
    public array $role_objects;

    /** @var array<string, string> */
    public array $role_names;
}
