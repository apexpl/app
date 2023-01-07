<?php
declare(strict_types = 1);

namespace Apex\App\Base\Lists;

/**
 * Timezone list
 */
class TimezoneList
{

    // Set timezone options
    public static array $opt = [
        'DST' => ['name' => '(DST -1200) Dateline Standard Time -- International Date Line West', 'offset' => -720, 'is_dst' => false], 
        'HST' => ['name' => '(HST -1000) Hawaiian Standard Time -- Hawaii', 'offset' => -600, 'is_dst' => false], 
        'PST' => ['name' => '(PST -0800) Pacific Standard Time -- Pacific Time (US & Canada)', 'offset' => -480, 'is_dst' => false], 
        'AKDT' => ['name' => '(AKDT -0800) Alaskan Standard Time -- Alaska', 'offset' => -480, 'is_dst' => true], 
        'UMST' => ['name' => '(UMST -0700) US Mountain Standard Time -- Arizona', 'offset' => -420, 'is_dst' => false], 
        'PDT' => ['name' => '(PDT -0700) Pacific Daylight Time -- Pacific Time (US & Canada)', 'offset' => -420, 'is_dst' => true], 
        'CCST' => ['name' => '(CCST -0600) Canada Central Standard Time -- Saskatchewan', 'offset' => -360, 'is_dst' => false], 
        'SPST' => ['name' => '(SPST -0500) SA Pacific Standard Time -- Bogota, Lima, Quito', 'offset' => -300, 'is_dst' => false], 
        'CDT' => ['name' => '(CDT -0500) Central Standard Time (Mexico) -- Guadalajara, Mexico City, Monterrey', 'offset' => -300, 'is_dst' => true], 
        'UEDT' => ['name' => '(UEDT -0400) US Eastern Standard Time -- Indiana (East)', 'offset' => -240, 'is_dst' => true], 
        'CBST' => ['name' => '(CBST -0400) Central Brazilian Standard Time -- Cuiaba', 'offset' => -240, 'is_dst' => false], 
        'SWST' => ['name' => '(SWST -0400) SA Western Standard Time -- Georgetown, La Paz, Manaus, San Juan', 'offset' => -240, 'is_dst' => false], 
        'EDT' => ['name' => '(EDT -0400) Eastern Standard Time -- Eastern Time (US & Canada)', 'offset' => -240, 'is_dst' => true], 
        'PYT' => ['name' => '(PYT -0400) Paraguay Standard Time -- Asuncion', 'offset' => -240, 'is_dst' => false], 
        'PSST' => ['name' => '(PSST -0400) Pacific SA Standard Time -- Santiago', 'offset' => -240, 'is_dst' => false], 
        'ESAST' => ['name' => '(ESAST -0300) E. South America Standard Time -- Brasilia', 'offset' => -180, 'is_dst' => false], 
        'SEST' => ['name' => '(SEST -0300) SA Eastern Standard Time -- Cayenne, Fortaleza', 'offset' => -180, 'is_dst' => false], 
        'NDT' => ['name' => '(NDT -0230) Newfoundland Standard Time -- Newfoundland', 'offset' => -150, 'is_dst' => true], 
        'CVST' => ['name' => '(CVST -0100) Cape Verde Standard Time -- Cape Verde Is.', 'offset' => -60, 'is_dst' => false], 
        'UTC' => ['name' => '(UTC +0000) UTC -- Coordinated Universal Time', 'offset' => 0, 'is_dst' => false], 
        'WCAST' => ['name' => '(WCAST +0100) W. Central Africa Standard Time -- West Central Africa', 'offset' => 60, 'is_dst' => false], 
        'MDT' => ['name' => '(MDT +0100) Morocco Standard Time -- Casablanca', 'offset' => 60, 'is_dst' => true], 
        'LST' => ['name' => '(LST +0200) Libya Standard Time -- Tripoli', 'offset' => 120, 'is_dst' => false], 
        'WEDT' => ['name' => '(WEDT +0200) W. Europe Standard Time -- Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna', 'offset' => 120, 'is_dst' => true], 
        'RDT' => ['name' => '(RDT +0200) Romance Standard Time -- Brussels, Copenhagen, Madrid, Paris', 'offset' => 120, 'is_dst' => true], 
        'CEDT' => ['name' => '(CEDT +0200) Central European Standard Time -- Sarajevo, Skopje, Warsaw, Zagreb', 'offset' => 120, 'is_dst' => true], 
        'MSK' => ['name' => '(MSK +0300) Moscow Standard Time -- Moscow, St. Petersburg, Volgograd', 'offset' => 180, 'is_dst' => false], 
        'SDT' => ['name' => '(SDT +0300) Syria Standard Time -- Damascus', 'offset' => 180, 'is_dst' => true], 
        'MEDT' => ['name' => '(MEDT +0300) Middle East Standard Time -- Beirut', 'offset' => 180, 'is_dst' => true], 
        'EEDT' => ['name' => '(EEDT +0300) E. Europe Standard Time -- E. Europe', 'offset' => 180, 'is_dst' => true], 
        'JST' => ['name' => '(JST +0300) Jordan Standard Time -- Amman', 'offset' => 180, 'is_dst' => false], 
        'GDT' => ['name' => '(GDT +0300) GTB Standard Time -- Athens, Bucharest', 'offset' => 180, 'is_dst' => true], 
        'TDT' => ['name' => '(TDT +0300) Turkey Standard Time -- Istanbul', 'offset' => 180, 'is_dst' => false], 
        'JDT' => ['name' => '(JDT +0300) Israel Standard Time -- Jerusalem', 'offset' => 180, 'is_dst' => true], 
        'FDT' => ['name' => '(FDT +0300) FLE Standard Time -- Helsinki, Kyiv, Riga, Sofia, Tallinn, Vilnius', 'offset' => 180, 'is_dst' => true], 
        'GST' => ['name' => '(GST +0400) Georgian Standard Time -- Tbilisi', 'offset' => 240, 'is_dst' => false], 
        'SAMT' => ['name' => '(SAMT +0400) Samara Time -- Samara, Ulyanovsk, Saratov', 'offset' => 240, 'is_dst' => false], 
        'AST' => ['name' => '(AST +0430) Afghanistan Standard Time -- Kabul', 'offset' => 270, 'is_dst' => false], 
        'IDT' => ['name' => '(IDT +0430) Iran Standard Time -- Tehran', 'offset' => 270, 'is_dst' => true], 
        'PKT' => ['name' => '(PKT +0500) Pakistan Standard Time -- Islamabad, Karachi', 'offset' => 300, 'is_dst' => false], 
        'ADT' => ['name' => '(ADT +0500) Azerbaijan Standard Time -- Baku', 'offset' => 300, 'is_dst' => true], 
        'SLST' => ['name' => '(SLST +0530) Sri Lanka Standard Time -- Sri Jayawardenepura', 'offset' => 330, 'is_dst' => false], 
        'IST' => ['name' => '(IST +0530) India Standard Time -- Chennai, Kolkata, Mumbai, New Delhi', 'offset' => 330, 'is_dst' => false], 
        'NST' => ['name' => '(NST +5.7500) Nepal Standard Time -- Kathmandu', 'offset' => 345, 'is_dst' => false], 
        'EST' => ['name' => '(EST +0600) Ekaterinburg Standard Time -- Ekaterinburg', 'offset' => 360, 'is_dst' => false], 
        'BST' => ['name' => '(BST +0600) Bangladesh Standard Time -- Dhaka', 'offset' => 360, 'is_dst' => false], 
        'SAST' => ['name' => '(SAST +0700) SE Asia Standard Time -- Bangkok, Hanoi, Jakarta', 'offset' => 420, 'is_dst' => false], 
        'NCAST' => ['name' => '(NCAST +0700) N. Central Asia Standard Time -- Novosibirsk', 'offset' => 420, 'is_dst' => false], 
        'CST' => ['name' => '(CST +0800) China Standard Time -- Beijing, Chongqing, Hong Kong, Urumqi', 'offset' => 480, 'is_dst' => false], 
        'WAST' => ['name' => '(WAST +0800) W. Australia Standard Time -- Perth', 'offset' => 480, 'is_dst' => false], 
        'MPST' => ['name' => '(MPST +0800) Singapore Standard Time -- Kuala Lumpur, Singapore', 'offset' => 480, 'is_dst' => false], 
        'NAST' => ['name' => '(NAST +0800) North Asia Standard Time -- Krasnoyarsk', 'offset' => 480, 'is_dst' => false], 
        'UST' => ['name' => '(UST +0800) Ulaanbaatar Standard Time -- Ulaanbaatar', 'offset' => 480, 'is_dst' => false], 
        'KST' => ['name' => '(KST +0900) Korea Standard Time -- Seoul', 'offset' => 540, 'is_dst' => false], 
        'NAEST' => ['name' => '(NAEST +0900) North Asia East Standard Time -- Irkutsk', 'offset' => 540, 'is_dst' => false], 
        'CAST' => ['name' => '(CAST +0930) Cen. Australia Standard Time -- Adelaide', 'offset' => 570, 'is_dst' => false], 
        'ACST' => ['name' => '(ACST +0930) AUS Central Standard Time -- Darwin', 'offset' => 570, 'is_dst' => false], 
        'EAST' => ['name' => '(EAST +1000) E. Australia Standard Time -- Brisbane', 'offset' => 600, 'is_dst' => false], 
        'WPST' => ['name' => '(WPST +1000) West Pacific Standard Time -- Guam, Port Moresby', 'offset' => 600, 'is_dst' => false], 
        'YST' => ['name' => '(YST +1000) Yakutsk Standard Time -- Yakutsk', 'offset' => 600, 'is_dst' => false], 
        'AEST' => ['name' => '(AEST +1000) AUS Eastern Standard Time -- Canberra, Melbourne, Sydney', 'offset' => 600, 'is_dst' => false], 
        'VST' => ['name' => '(VST +1100) Vladivostok Standard Time -- Vladivostok', 'offset' => 660, 'is_dst' => false], 
        'CPST' => ['name' => '(CPST +1100) Central Pacific Standard Time -- Solomon Is., New Caledonia', 'offset' => 660, 'is_dst' => false], 
        'U' => ['name' => '(U +1200) UTC+12 -- Coordinated Universal Time+12', 'offset' => 720, 'is_dst' => false], 
        'FST' => ['name' => '(FST +1200) Fiji Standard Time -- Fiji', 'offset' => 720, 'is_dst' => false], 
        'NZST' => ['name' => '(NZST +1200) New Zealand Standard Time -- Auckland, Wellington', 'offset' => 720, 'is_dst' => false], 
        'MST' => ['name' => '(MST +1200) Magadan Standard Time -- Magadan', 'offset' => 720, 'is_dst' => false], 
        'TST' => ['name' => '(TST +1300) Tonga Standard Time -- Nuku\'alofa', 'offset' => 780, 'is_dst' => false], 
        'SST' => ['name' => '(SST +1300) Samoa Standard Time -- Samoa', 'offset' => 780, 'is_dst' => false], 
        'KDT' => ['name' => '(KDT +1300) Kamchatka Standard Time -- Petropavlovsk-Kamchatsky - Old', 'offset' => 780, 'is_dst' => true]
    ];

}
 
