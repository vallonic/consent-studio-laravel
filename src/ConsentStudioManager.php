<?php

namespace ConsentStudio\Laravel;

use Illuminate\Http\Request;

class ConsentStudioManager
{
    protected const COOKIE_PREFIX = 'consent-studio__';
    protected const COOKIE_STORAGE = self::COOKIE_PREFIX . 'storage';
    protected const COOKIE_SEEN = self::COOKIE_PREFIX . 'seen';
    protected const COOKIE_CONSENT_ID = self::COOKIE_PREFIX . 'consent-id';

    protected ?Request $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Check if the user has seen the consent banner.
     *
     * @return bool Returns false if null or not set
     */
    public function seenBanner(): bool
    {
        $value = $this->getCookie(self::COOKIE_SEEN);

        if ($value === null || $value === '') {
            return false;
        }

        // Handle string values like 'true', 'false', '1', '0'
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }

    /**
     * Get the current user's consent ID.
     *
     * @return string|null
     */
    public function id(): ?string
    {
        $value = $this->getCookie(self::COOKIE_CONSENT_ID);

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    /**
     * Check if a specific consent category is granted.
     *
     * @param string $category The consent category (functional, analytics, marketing, neutral)
     * @return bool
     */
    public function granted(string $category): bool
    {
        $grantedConsents = $this->getGrantedConsents();

        return in_array($category, $grantedConsents, true);
    }

    /**
     * Alias for granted() - check if a specific consent category is granted.
     *
     * @param string $category The consent category
     * @return bool
     */
    public function has(string $category): bool
    {
        return $this->granted($category);
    }

    /**
     * Check if any of the specified categories are granted.
     *
     * @param array $categories Array of consent categories
     * @return bool
     */
    public function any(array $categories): bool
    {
        $grantedConsents = $this->getGrantedConsents();

        foreach ($categories as $category) {
            if (in_array($category, $grantedConsents, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all granted consent categories or check if all specified categories are granted.
     *
     * @param array|null $categories Optional array of categories to check
     * @return array|bool Returns array of granted consents if no argument, bool if checking specific categories
     */
    public function all(?array $categories = null): array|bool
    {
        $grantedConsents = $this->getGrantedConsents();

        // If no categories specified, return all granted consents
        if ($categories === null) {
            return $grantedConsents;
        }

        // Check if all specified categories are granted
        foreach ($categories as $category) {
            if (!in_array($category, $grantedConsents, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the complete consent state.
     *
     * @return array
     */
    public function state(): array
    {
        return [
            'id' => $this->id(),
            'seen' => $this->seenBanner(),
            'consents' => $this->getGrantedConsents(),
        ];
    }

    /**
     * Get all granted consent categories from the storage cookie.
     *
     * @return array
     */
    protected function getGrantedConsents(): array
    {
        $value = $this->getCookie(self::COOKIE_STORAGE);

        if ($value === null || $value === '') {
            return [];
        }

        // The cookie value is URL-encoded JSON like: [%22functional%22%2C%22analytics%22]
        $decoded = urldecode($value);
        $consents = json_decode($decoded, true);

        if (!is_array($consents)) {
            return [];
        }

        return $consents;
    }

    /**
     * Get a cookie value from the request.
     *
     * @param string $key
     * @return string|null
     */
    protected function getCookie(string $key): ?string
    {
        // If no request is available (CLI, queue jobs, etc.), return null
        if ($this->request === null) {
            return null;
        }

        return $this->request->cookie($key);
    }
}
