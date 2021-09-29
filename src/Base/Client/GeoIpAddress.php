<?php
declare(strict_types = 1);

namespace Apex\App\Base\Client;

use Apex\Armor\Auth\Operations\LookupIP;
use Apex\App\Base\Lists\CountryList;

/**
 * Geo IP address
 */
class GeoIpAddress
{

    // Properties
    private bool $done_lookup = false;
    private string $ip_address;
    private string $geo_country;
    private string $geo_province_name;
    private string $geo_province_iso_code;
    private string $geo_city;
    private float $geo_latitude;
    private float $geo_longitude;
    private array $country_opt;

    /**
     * Constructor
     */
    public function __construct(
        string $ip_address = ''
    ) { 
        $this->obtainIp($ip_address);
    }

    /**
     * Obtain IP
     */
    protected function obtainIp(string $ip_address):void
    {

        // If IP is defined
        if ($ip_address != '') { 
            $this->ip_address = $ip_address;
            return;
    }

        // Determine IP
        $this->ip_address = match(true) { 
            isset($_SERVER['HTTP_X_REAL_IP']) => $_SERVER['HTTP_X_REAL_IP'], 
            isset($_SERVER['HTTP_X_FORWARDED_FOR']) => $_SERVER['HTTP_X_FORWARDED_FOR'], 
            isset($_SERVER['REMOTE_ADDR']) => $_SERVER['REMOTE_ADDR'], 
            default => '127.0.0.1'
        };

    }


    /**
     * Get IP address
     */
    public function getIpAddress():string
    {
        return $this->ip_address;
    }

    /**
     * Get country code
     */
    public function getCountryCode():string
    {
        $this->lookup();
        return $this->geo_country;
    }

    /**
     * Get country name
     */
    public function getCountryName():string
    {
        $this->lookup();
        return $this->country_opt['name'] ?? '';
    }

    /**
     * Get country currency
     */
    public function getCountryCurrency():string
    {
        $this->lookup();
        return $this->country_opt['currency'] ?? '';
    }

    /**
     * Get country timezone
     */
    public function getCountryTimezone():string
    {
        $this->lookup();
        return $this->country_opt['timezone'] ?? '';
    }

    /**
     * Get country calling code
     */
    public function getCountryCallingcode():string
    {
        $this->lookup();
        return $this->country_opt['calling_code'] ?? '';
    }

    /**
     * Get country tld
     */
    public function getCountryTld():string
    {
        $this->lookup();
        return $this->country_opt['tld'] ?? '';
    }

    /**
     * Get province code
     */
    public function getProvinceCode():string
    {
        $this->lookup();
        return $this->geo_province_iso_code;
    }

    /**
     * Get province name
     */
    public function getProvinceName():string
    {
        $this->lookup();
        return $this->geo_province_name;
    }

    /**
     * Get city
     */
    public function getCity():string
    {
        return $this->geo_city;
    }

    /**
     * Get latitude
     */
    public function getLatitude():float
    {
        return $this->geo_latitude;
    }

    /**
     * Get longitude
     */
    public function getLongitude():float
    {
        $this->lookup();
        return $this->geo_longitude;
    }

    /**
     * Lookup IP
     */
    private function lookup():void
    {

        // Check if done
        if ($this->done_lookup === true) { 
            return;
        }

        // Lookup ip
        $res = LookupIP::query($this->ip_address);
        foreach ($res as $key => $value) {
            $key = 'geo_' . $key; 
            $this->$key = $value;
        }

        // Set properties
        $this->country_opt = CountryList::$opt[$this->geo_country] ?? [];
        $this->done_lookup = true;
    }

}




