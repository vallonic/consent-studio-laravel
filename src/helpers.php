<?php

use ConsentStudio\Laravel\ConsentStudioManager;

if (!function_exists('consent')) {
    /**
     * Helper function to access consent functionality.
     *
     * Usage:
     * - consent()                              -> Returns ConsentStudioManager instance for chaining
     * - consent('marketing')                   -> Returns bool: is 'marketing' granted?
     * - consent(['marketing', 'analytics'])    -> Returns bool: is any of them granted?
     *
     * @param string|array|null $category Optional category or array of categories to check
     * @return ConsentStudioManager|bool
     */
    function consent(string|array|null $category = null): ConsentStudioManager|bool
    {
        $manager = app('consent-studio');

        // No argument: return manager for chaining
        if ($category === null) {
            return $manager;
        }

        // Array argument: check if any of the categories are granted
        if (is_array($category)) {
            return $manager->any($category);
        }

        // String argument: check if the category is granted
        return $manager->granted($category);
    }
}
