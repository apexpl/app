<?php
declare(strict_types = 1);

namespace Apex\App\Base\Client;

use Apex\App\Base\Lists\{CountryList, CurrencyList, TimezoneList, LanguageList};
use Apex\App\Exceptions\ApexInvalidArgumentException;

/**
 * Client info model
 */
class ClientInfo extends GeoIpAddress
{

    // Properties
    private string $browser;
    private string $browser_version;
    private string $platform;

    /**
     * Constructor
     */
    public function __construct(
        private string $uuid = '', 
        string $ip_address = '', 
        private string $user_agent = '', 
        private string $area = 'public', 
        private bool $prefix_menu_links = false,
        private string $currency = 'USD', 
        private string $timezone = 'PST', 
        private string $language = 'en'
    ) { 

        // Get IP address
        $this->obtainIp($ip_address);

        // Get user agent
        if ($this->user_agent == '') { 
            $this->user_agent = $this->server['HTTP_USER_AGENT'] ?? '';
        }

    }

    /**
     * Get uuid
     */
    public function getUuid():string
    {
        return $this->uuid;
    }

    /**
     * Get user agent
     */
    public function getUserAgent():string
    {
        return $this->user_agent;
    }

    /**
     * Get area
     */
    public function getArea():string
    {
        return $this->area;
    }

    /**
     * Get prefix menu links
     */
    public function getPrefixMenuLinks():bool
    {
        return $this->prefix_menu_links;
    }

    /**
     * Get currency
     */
    public function getCurrency():string
    {
        return $this->currency;
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol():string
    {
        return CurrencyList::$opt[$this->currency]['symbol'] ?? '$';
    }

    /**
     * Get currency decimals
     */
    public function getCurrencyDecimals():int
    {
        return CurrencyList::$opt[$this->currency]['decimals'] ?? 2;
    }

    /**
     * Get currency - is crypto?
     */
    public function getCurrencyIsCrypto():bool
    {
        return CurrencyList::$opt[$this->currency]['is_crypto'] ?? false;
    }

    /**
     * Get timezone
     */
    public function getTimezone():string
    {
        return $this->timezone;
    }

    /**
     * Get timezone offset
     */
    public function getTimezoneOffset():int
    {
        return TimezoneList::$opt[$this->timezone]['offset'];
    }

    /**
     * Get timezone dst
     */
    public function getTimezoneDst():bool
    {
        return TimezoneList::$opt[$this->timezone]['is_dst'];
    }

    /**
     * Get language
     */
    public function getLanguage():string
    {
        return $this->language;
    }

    /**
     * Get browser
     */
    public function getBrowser():string
    {

        if (!isset($this->browser)) { 
            $this->parseUserAgent();
        }
        return $this->browser;
    }

    /**
     * Get browser version
     */
    public function getBrowserVersion():string
    {

        if (!isset($this->browser)) { 
            $this->parseUserAgent();
        }
        return $this->browser_version;
    }

    /**
     * Get platform
     */
    public function getPlatform():string
    {

        if (!isset($this->browser)) { 
            $this->parseUserAgent();
        }
        return $this->platform;
    }

    /**
     * Parse user agent
     */
    private function parseUserAgent():void
    {

        // Parse
        if ($this->user_agent == '' || !$ua = parse_user_agent($this->user_agent)) { 
            $this->browser = 'unknown';
            $this->browser_version = '0.0';
            $this->platform = 'unknown';
        } else { 
            $this->browser = $ua['browser'];
            $this->browser_version = $ua['bersion'];
            $this->platform = $ua['platform'];
        }

    }

    /**
     * Set uuid
     */
    public function setUuid(string $uuid):void
    {
        $this->uuid = $uuid;
    }

    /**
     * Set area
     */
    public function setArea(string $area):void
    {
        $this->area = $area;
    }

    /**
     * Set prefix menu links
     */
    public function setPrefixMenuLinks(bool $prefix):void
    {
        $this->prefix_menu_links = $prefix;
    }

    /**
     * Set timezone
     */
    public function setTimezone(string $timezone):void
    {

        // Check
        if (!isset(TimezoneList::$opt[$timezone])) { 
            throw new ApexInvalidArgumentException("Unable to set timezone, as the timezone does not exist, $timezone");
        }
        $this->timezone = $timezone;
    }

    /**
     * set language
     */
    public function setLanguage(string $language):void
    {

        // Check
        if (!isset(LanguageList::$opt[$language])) { 
            throw new ApexInvalidArgumentException("Unable to set language as language does not exist, $language");
        }
        $this->language = $language;
    }

}


