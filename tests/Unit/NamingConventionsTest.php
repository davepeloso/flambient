<?php

namespace Tests\Unit;

use App\Constants\NamingConventions;
use PHPUnit\Framework\TestCase;

class NamingConventionsTest extends TestCase
{
    /**
     * Test database table names follow conventions
     */
    public function test_database_table_names_follow_convention(): void
    {
        foreach (NamingConventions::TABLE_NAMES as $key => $tableName) {
            // Check prefix
            $this->assertStringStartsWith(
                NamingConventions::DB_PREFIX,
                $tableName,
                "Table name should start with the defined prefix"
            );

            // Check snake_case format
            $this->assertMatchesRegularExpression(
                '/^[a-z]+[a-z_]*[a-z]+$/',
                str_replace(NamingConventions::DB_PREFIX, '', $tableName),
                "Table name should be in snake_case format"
            );
        }
    }

    /**
     * Test model names follow conventions
     */
    public function test_model_names_follow_convention(): void
    {
        foreach (NamingConventions::MODELS as $key => $modelName) {
            // Check PascalCase format
            $this->assertMatchesRegularExpression(
                '/^[A-Z][a-zA-Z]*$/',
                $modelName,
                "Model name should be in PascalCase format"
            );
        }
    }

    /**
     * Test route names follow conventions
     */
    public function test_route_names_follow_convention(): void
    {
        foreach (NamingConventions::ROUTES as $key => $route) {
            // Check URL format (kebab-case)
            if ($route['url'] !== '/') {
                $urlParts = explode('/', trim($route['url'], '/'));
                foreach ($urlParts as $part) {
                    if (!str_contains($part, '{')) {
                        $this->assertMatchesRegularExpression(
                            '/^[a-z]+(-[a-z]+)*$/',
                            $part,
                            "URL segments should be in kebab-case format"
                        );
                    }
                }
            }

            // Check route name format (dot notation)
            $this->assertMatchesRegularExpression(
                '/^[a-z]+(\.[a-z]+)*$/',
                $route['name'],
                "Route name should be in dot notation format"
            );
        }
    }

    /**
     * Test view names follow conventions
     */
    public function test_view_names_follow_convention(): void
    {
        $this->validateViewNames(NamingConventions::VIEWS);
    }

    /**
     * Test CSS classes follow conventions
     */
    public function test_css_classes_follow_convention(): void
    {
        $this->validateCssClasses(NamingConventions::CSS_CLASSES);
    }

    /**
     * Test element IDs follow conventions
     */
    public function test_element_ids_follow_convention(): void
    {
        // Test regular element ID
        $elementId = NamingConventions::generateElementId('batch', 'form', 'submit', false);
        $this->assertMatchesRegularExpression(
            '/^[a-z]+-[a-z]+-[a-z]+$/',
            $elementId,
            "Element ID should follow the pattern {category}-{type}-{purpose}"
        );

        // Test test element ID
        $testElementId = NamingConventions::generateElementId('batch', 'form', 'submit', true);
        $this->assertStringStartsWith('test-', $testElementId);
        $this->assertMatchesRegularExpression(
            '/^test-[a-z]+-[a-z]+-[a-z]+$/',
            $testElementId,
            "Test element ID should follow the pattern test-{category}-{type}-{purpose}"
        );

        // Test invalid inputs
        $this->expectException(\InvalidArgumentException::class);
        NamingConventions::generateElementId('invalid', 'form', 'submit', false);
    }

    /**
     * Test command parameters follow conventions
     */
    public function test_command_params_follow_convention(): void
    {
        foreach (NamingConventions::COMMAND_PARAMS as $category => $params) {
            foreach ($params as $key => $param) {
                // Check snake_case format
                $this->assertMatchesRegularExpression(
                    '/^[a-z]+(_[a-z]+)*$/',
                    $param,
                    "Command parameter should be in snake_case format"
                );

                // Check that param includes category
                $this->assertStringContainsString(
                    $category,
                    $param,
                    "Command parameter should include its category"
                );
            }
        }
    }

    /**
     * Helper method to validate view names recursively
     */
    private function validateViewNames(array $views, string $prefix = ''): void
    {
        foreach ($views as $key => $value) {
            if (is_array($value)) {
                $this->validateViewNames($value, $prefix ? "$prefix.$key" : $key);
            } else {
                // Check dot notation format
                $this->assertMatchesRegularExpression(
                    '/^[a-z]+(\.[a-z]+)*$/',
                    $value,
                    "View name should be in dot notation format"
                );
            }
        }
    }

    /**
     * Helper method to validate CSS classes recursively
     */
    private function validateCssClasses(array $classes): void
    {
        foreach ($classes as $key => $value) {
            if (is_array($value)) {
                $this->validateCssClasses($value);
            } else {
                if (str_starts_with($value, 'flambient-')) {
                    // Check BEM-style classes
                    $this->assertMatchesRegularExpression(
                        '/^flambient-[a-z]+(-[a-z]+)*(__[a-z]+(-[a-z]+)*)?(--[a-z]+(-[a-z]+)*)?$/',
                        $value,
                        "CSS class should follow BEM convention with flambient prefix"
                    );
                } else {
                    // Check utility classes
                    $this->assertMatchesRegularExpression(
                        '/^[a-z]+(-[0-9]+)?$/',
                        $value,
                        "Utility class should be in kebab-case format"
                    );
                }
            }
        }
    }
}
