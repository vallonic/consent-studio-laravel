<?php

namespace ConsentStudio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool seenBanner() Check if the user has seen the consent banner
 * @method static string|null id() Get the current user's consent ID
 * @method static bool granted(string $category) Check if a specific consent category is granted
 * @method static bool has(string $category) Alias for granted() - check if a specific consent category is granted
 * @method static bool any(array $categories) Check if any of the specified categories are granted
 * @method static array|bool all(array|null $categories = null) Get all granted consents or check if all specified categories are granted
 * @method static array state() Get the complete consent state
 *
 * @see \ConsentStudio\Laravel\ConsentStudioManager
 */
class ConsentStudio extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'consent-studio';
    }
}
