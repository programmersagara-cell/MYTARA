<?php
/**
 * View Renderer
 */

namespace App\Core;

class View
{
    private array $sections = [];
    private string $currentSection = '';
    private string $cachePath;

    public function __construct()
    {
        $this->cachePath = CACHE_PATH . '/views';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    /**
     * Render a view file
     */
    public function render(string $view, array $data = []): void
    {
        $viewPath = $this->resolveViewPath($view);

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Extract data as variables
        extract($data);

        // Start output buffering
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // Merge view-set variables (like $extraStyles, $extraScripts, $layout) into data
        // so they are available in the layout rendering context
        if (isset($extraStyles)) {
            $data['extraStyles'] = $extraStyles;
        }
        if (isset($extraScripts)) {
            $data['extraScripts'] = $extraScripts;
        }

        // If a layout is specified, wrap content with it; otherwise use default layout
        if (isset($layout)) {
            $this->renderWithLayout($layout, $data, $content);
        } else {
            // Wrap in main layout if not specified
            $this->renderWithLayout('layouts/main', $data, $content);
        }
    }

    /**
     * Render with a specific layout
     */
    public function renderWithLayout(string $layout, array $data, string $content): void
    {
        $layoutPath = $this->resolveViewPath($layout);

        if (!file_exists($layoutPath)) {
            echo $content;
            return;
        }

        extract($data);
        
        // Store the sections content
        $sections = $this->sections;
        
        // The content variable is available in the layout
        include $layoutPath;
    }

    /**
     * Start a section
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End the current section
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    /**
     * Render a section
     */
    public function renderSection(string $name): void
    {
        if (isset($this->sections[$name])) {
            echo $this->sections[$name];
        }
    }

    /**
     * Include a partial view
     */
    public function partial(string $partial, array $data = []): void
    {
        $path = $this->resolveViewPath("partials/{$partial}");
        if (file_exists($path)) {
            extract($data);
            include $path;
        }
    }

    /**
     * Escape HTML output
     */
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Resolve view file path
     */
    private function resolveViewPath(string $view): string
    {
        // Remove leading slash just in case
        $view = ltrim($view, '/');
        return VIEWS_PATH . '/' . $view . '.php';
    }
}

