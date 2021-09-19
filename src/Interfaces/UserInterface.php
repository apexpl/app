<?php

namespace apex\App\Interfaces;

use Apex\Armor\Interfaces\ArmorUserInterface;

/**
 * User interface
 * 
 * Extends Apex\Armor\Interfaces\ArmorUserInterface, plus adds some additional methods
 * to retrieve the full name, timezone, langugae and currency of the user.
 */
interface UserInterface extends ArmorUserInterface
{

    /**
     * Get full name
     */
    public function getFullName():string;

    /**
     * Get timezone
     */
    public function getTimezone():string;

    /*
     * GEt language
     */
    public function getLanguage():string;

    /**
     8 Get currency
     */
    public function getCurrency():string;

}




