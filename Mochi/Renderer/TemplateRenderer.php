<?php

namespace Mochi\Renderer;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TemplateRenderer {
    private Environment $twig;

    /**
     * Constructor for the TemplateRenderer.
     *
     * Initializes the Twig environment for rendering templates.
     *
     * @param array $config The configuration array which should include a 'twig' key.
     *                      The 'twig' key should be an array containing a 'views_path' key,
     *                      which specifies the directory path where the template files are located.
     *                      If no 'views_path' is provided, a default path is used.
     */
    public function __construct(array $config) {
        // Extract the 'views_path' from the provided configuration array.
        // If 'views_path' is not set, default to a directory named 'default/views' within the same directory as this script.
        $viewsPath = $config['twig']['views_path'] ?? __DIR__ . '/default/views';

        // Create a new Twig FilesystemLoader with the specified views path.
        // This loader is responsible for loading the template files from the filesystem.
        $loader = new FilesystemLoader($viewsPath);

        // Initialize the Twig Environment with the created loader.
        // The Environment is the main entry point of Twig and holds all the configurations.
        $this->twig = new Environment($loader);
    }

    /**
     * Renders a template using the Twig Environment.
     *
     * @param string $template The name of the template file to render.
     * @param array $data The data to pass to the template.
     * @return string The rendered template as a string.
     */
    public function render(string $template, array $data = []): string {
        return $this->twig->render($template, $data);
    }
}
