<?php

namespace App\Constants;

class NamingConventions
{
    // Database Tables and Columns
    public const DB_PREFIX = 'flambient_';
    public const TABLE_NAMES = [
        'batches' => self::DB_PREFIX . 'batches',
        'stacks' => self::DB_PREFIX . 'exposure_stacks',
        'images' => self::DB_PREFIX . 'images',
        'delineations' => self::DB_PREFIX . 'delineations',
        'payments' => self::DB_PREFIX . 'payments',
    ];

    // Model Names (PascalCase)
    public const MODELS = [
        'batch' => 'Batch',
        'stack' => 'ExposureStack',
        'image' => 'Image',
        'delineation' => 'MetaDelineation',
        'payment' => 'Payment',
    ];

    // Route Names (kebab-case for URLs, dot notation for named routes)
    public const ROUTES = [
        'home' => [
            'url' => '/',
            'name' => 'home',
        ],
        'upload' => [
            'url' => '/upload',
            'name' => 'upload',
        ],
        'gallery' => [
            'url' => '/gallery',
            'name' => 'gallery',
        ],
        'batch' => [
            'url' => '/batch/{id}',
            'name' => 'batch.show',
        ],
        'batch_download' => [
            'url' => '/batch/{id}/download',
            'name' => 'batch.download',
        ],
    ];

    // View Names (dot notation)
    public const VIEWS = [
        'layouts' => [
            'app' => 'layouts.app',
        ],
        'pages' => [
            'home' => 'pages.home',
            'upload' => 'pages.upload',
            'gallery' => 'pages.gallery',
            'batch' => 'pages.batch.show',
            'download' => 'pages.batch.download',
        ],
        'components' => [
            'header' => 'components.header',
            'footer' => 'components.footer',
            'upload_form' => 'components.forms.upload',
            'batch_preview' => 'components.previews.batch',
            'stack_preview' => 'components.previews.stack',
        ],
    ];

    // CSS Classes (BEM-inspired with flambient prefix)
    public const CSS_CLASSES = [
        'layout' => [
            'container' => 'flambient-container',
            'header' => 'flambient-header',
            'footer' => 'flambient-footer',
            'main' => 'flambient-main',
        ],
        'components' => [
            'button' => [
                'base' => 'flambient-button',
                'primary' => 'flambient-button--primary',
                'secondary' => 'flambient-button--secondary',
            ],
            'card' => [
                'base' => 'flambient-card',
                'terminal' => 'flambient-card--terminal',
            ],
            'form' => [
                'group' => 'flambient-form__group',
                'label' => 'flambient-form__label',
                'input' => 'flambient-form__input',
                'error' => 'flambient-form__error',
            ],
        ],
        'utilities' => [
            'margin' => [
                'top' => [
                    'sm' => 'mt-2',
                    'md' => 'mt-4',
                    'lg' => 'mt-8',
                ],
            ],
            'text' => [
                'title' => 'flambient-title',
                'subtitle' => 'flambient-subtitle',
                'body' => 'flambient-text',
            ],
        ],
    ];

    // Element IDs (for frontend elements)
    public const ELEMENT_IDS = [
        'form' => [
            'pattern' => '{category}-{type}-{purpose}',
            'categories' => ['batch', 'stack', 'image', 'payment'],
            'types' => ['form', 'field', 'container', 'results'],
        ],
        // Test-specific IDs (only for testing)
        'test' => [
            'pattern' => 'test-{category}-{type}-{purpose}',
            'categories' => ['batch', 'stack', 'image', 'payment'],
            'types' => ['form', 'field', 'container', 'results'],
        ],
    ];

    // Command Parameters
    public const COMMAND_PARAMS = [
        'batch' => [
            'id' => 'batch_id',
            'status' => 'batch_status',
            'expiry' => 'batch_expiry',
        ],
        'stack' => [
            'id' => 'stack_id',
            'type' => 'stack_type',
            'count' => 'stack_count',
        ],
        'image' => [
            'id' => 'image_id',
            'type' => 'image_type',
            'exposure' => 'image_exposure',
        ],
    ];

    /**
     * Get a database table name
     */
    public static function getTableName(string $key): ?string
    {
        return self::TABLE_NAMES[$key] ?? null;
    }

    /**
     * Get a model name
     */
    public static function getModelName(string $key): ?string
    {
        return self::MODELS[$key] ?? null;
    }

    /**
     * Get a route configuration
     */
    public static function getRoute(string $key): ?array
    {
        return self::ROUTES[$key] ?? null;
    }

    /**
     * Get a view path
     */
    public static function getView(string $category, string $key): ?string
    {
        return self::VIEWS[$category][$key] ?? null;
    }

    /**
     * Get a CSS class
     */
    public static function getCssClass(string $category, string $component, ?string $variant = null): ?string
    {
        $class = self::CSS_CLASSES[$category][$component] ?? null;
        if (is_array($class) && $variant) {
            return $class[$variant] ?? null;
        }
        return $class;
    }

    /**
     * Generate an element ID
     */
    public static function generateElementId(string $category, string $type, string $purpose, bool $isTest = false): string
    {
        $config = $isTest ? self::ELEMENT_IDS['test'] : self::ELEMENT_IDS['form'];

        if (!in_array($category, $config['categories']) ||
            !in_array($type, $config['types'])) {
            throw new \InvalidArgumentException('Invalid category or type for element ID');
        }

        return str_replace(
            ['{category}', '{type}', '{purpose}'],
            [$category, $type, $purpose],
            $config['pattern']
        );
    }

    /**
     * Get a command parameter
     */
    public static function getCommandParam(string $category, string $key): ?string
    {
        return self::COMMAND_PARAMS[$category][$key] ?? null;
    }
}
