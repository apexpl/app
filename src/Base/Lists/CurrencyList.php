<?php
declare(strict_types = 1);

namespace Apex\App\Base\Lists;

/**
 * Currency list
 */
class CurrencyList
{

    // Set currency options
    public static array $opt = [
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'AU$', 'decimals' => 2, 'is_crypto' => false], 
        'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'decimals' => 2, 'is_crypto' => false], 
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimals' => 2, 'is_crypto' => false], 
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'CA$', 'decimals' => 2, 'is_crypto' => false], 
        'CLP' => ['name' => 'Chilean Peso', 'symbol' => 'CL$', 'decimals' => 2, 'is_crypto' => false], 
        'CNY' => ['name' => 'Chinese Yuan', 'symbol' => 'CN¥', 'decimals' => 2, 'is_crypto' => false], 
        'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'decimals' => 2, 'is_crypto' => false], 
        'DKK' => ['name' => 'Danish Krone', 'symbol' => 'Dkr', 'decimals' => 2, 'is_crypto' => false], 
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'is_crypto' => false], 
        'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'decimals' => 2, 'is_crypto' => false], 
        'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft', 'decimals' => 2, 'is_crypto' => false], 
        'INR' => ['name' => 'Indian Rupee', 'symbol' => 'Rs', 'decimals' => 2, 'is_crypto' => false], 
        'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimals' => 2, 'is_crypto' => false], 
        'ILS' => ['name' => 'Israeli New Shekel', 'symbol' => '₪', 'decimals' => 2, 'is_crypto' => false], 
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 2, 'is_crypto' => false], 
        'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimals' => 2, 'is_crypto' => false], 
        'MXN' => ['name' => 'Mexican Peso', 'symbol' => 'MX$', 'decimals' => 2, 'is_crypto' => false], 
        'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'decimals' => 2, 'is_crypto' => false], 
        'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'Nkr', 'decimals' => 2, 'is_crypto' => false], 
        'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => 'PKRs', 'decimals' => 2, 'is_crypto' => false], 
        'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'decimals' => 2, 'is_crypto' => false], 
        'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł', 'decimals' => 2, 'is_crypto' => false], 
        'RUB' => ['name' => 'Russian Ruble', 'symbol' => 'RUB', 'decimals' => 2, 'is_crypto' => false], 
        'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimals' => 2, 'is_crypto' => false], 
        'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'decimals' => 2, 'is_crypto' => false], 
        'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'decimals' => 2, 'is_crypto' => false], 
        'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'Skr', 'decimals' => 2, 'is_crypto' => false], 
        'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimals' => 2, 'is_crypto' => false], 
        'TWD' => ['name' => 'Taiwan Dollar', 'symbol' => 'NT$', 'decimals' => 2, 'is_crypto' => false], 
        'THB' => ['name' => 'Thailand Baht', 'symbol' => '฿', 'decimals' => 2, 'is_crypto' => false], 
        'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺', 'decimals' => 2, 'is_crypto' => false], 
        '' => ['name' => '---------------', 'symbol' => '', 'decimals' => 2, 'is_crypto' => false], 
        'AFN' => ['name' => 'Afghan Afghani', 'symbol' => 'Af', 'decimals' => 2, 'is_crypto' => false], 
        'ALL' => ['name' => 'Albanian lek', 'symbol' => 'ALL', 'decimals' => 2, 'is_crypto' => false], 
        'DZD' => ['name' => 'Algerian Dinar', 'symbol' => 'DA', 'decimals' => 2, 'is_crypto' => false], 
        'AOA' => ['name' => 'Angolan Kwanza', 'symbol' => 'Kz', 'decimals' => 2, 'is_crypto' => false], 
        'ARS' => ['name' => 'Argentine peso', 'symbol' => 'AR$', 'decimals' => 2, 'is_crypto' => false], 
        'AMD' => ['name' => 'Armenian dram', 'symbol' => 'AMD', 'decimals' => 2, 'is_crypto' => false], 
        'AWG' => ['name' => 'Arubin florin', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'AZN' => ['name' => 'Azerbaijani manat', 'symbol' => 'man.', 'decimals' => 2, 'is_crypto' => false], 
        'BSD' => ['name' => 'Bahamian dollar', 'symbol' => 'B$', 'decimals' => 2, 'is_crypto' => false], 
        'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => 'BD', 'decimals' => 2, 'is_crypto' => false], 
        'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => 'Tk', 'decimals' => 2, 'is_crypto' => false], 
        'BBD' => ['name' => 'Barbadian dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'BYR' => ['name' => 'Belarusian ruble', 'symbol' => 'BYR', 'decimals' => 2, 'is_crypto' => false], 
        'BZD' => ['name' => 'Belize dollar', 'symbol' => 'BZ$', 'decimals' => 2, 'is_crypto' => false], 
        'BMD' => ['name' => 'Bermudian dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'BTN' => ['name' => 'Bhutanese Ngultrum', 'symbol' => 'Nu.', 'decimals' => 2, 'is_crypto' => false], 
        'BOB' => ['name' => 'Bolivian Boliviano', 'symbol' => 'Bs', 'decimals' => 2, 'is_crypto' => false], 
        'BAM' => ['name' => 'Bosnian Convertible Marka', 'symbol' => 'KM', 'decimals' => 2, 'is_crypto' => false], 
        'BWP' => ['name' => 'Botswana Pula', 'symbol' => 'BWP', 'decimals' => 2, 'is_crypto' => false], 
        'BND' => ['name' => 'Bruneian Dollar', 'symbol' => 'BN$', 'decimals' => 2, 'is_crypto' => false], 
        'BGN' => ['name' => 'Bulgarian lev', 'symbol' => 'BGN', 'decimals' => 2, 'is_crypto' => false], 
        'MMK' => ['name' => 'Burmese Kyat', 'symbol' => 'MMK', 'decimals' => 2, 'is_crypto' => false], 
        'BIF' => ['name' => 'Burundian Franc', 'symbol' => 'FBu', 'decimals' => 2, 'is_crypto' => false], 
        'XOF' => ['name' => 'CFA Franc', 'symbol' => 'CFA', 'decimals' => 2, 'is_crypto' => false], 
        'XPF' => ['name' => 'CFP Franc', 'symbol' => '', 'decimals' => 2, 'is_crypto' => false], 
        'KHR' => ['name' => 'Cambodian Riel', 'symbol' => 'KHR', 'decimals' => 2, 'is_crypto' => false], 
        'CVE' => ['name' => 'Cape Verdean Escudo', 'symbol' => 'CV$', 'decimals' => 2, 'is_crypto' => false], 
        'KYD' => ['name' => 'Caymanian Dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'COP' => ['name' => 'Colombian peso', 'symbol' => 'CO$', 'decimals' => 2, 'is_crypto' => false], 
        'KMF' => ['name' => 'Comoran Franc', 'symbol' => 'CF', 'decimals' => 2, 'is_crypto' => false], 
        'HRK' => ['name' => 'Croatian kuna', 'symbol' => 'kn', 'decimals' => 2, 'is_crypto' => false], 
        'CUC' => ['name' => 'Cuban convertible peso', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'CUP' => ['name' => 'Cuban peso', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'DJF' => ['name' => 'Djiboutian Franc', 'symbol' => 'Fdj', 'decimals' => 2, 'is_crypto' => false], 
        'DOP' => ['name' => 'Dominican peso', 'symbol' => 'RD$', 'decimals' => 2, 'is_crypto' => false], 
        'ANG' => ['name' => 'Dutch Guilder', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'XCD' => ['name' => 'East Caribbean dollar', 'symbol' => 'EC$', 'decimals' => 2, 'is_crypto' => false], 
        'EGP' => ['name' => 'Egyptian Pound', 'symbol' => 'EGP', 'decimals' => 2, 'is_crypto' => false], 
        'AED' => ['name' => 'Emirati Dirham', 'symbol' => 'AED', 'decimals' => 2, 'is_crypto' => false], 
        'ERN' => ['name' => 'Eritrean nakfa', 'symbol' => 'Nfk', 'decimals' => 2, 'is_crypto' => false], 
        'ETB' => ['name' => 'Ethiopian Birr', 'symbol' => 'Br', 'decimals' => 2, 'is_crypto' => false], 
        'FKP' => ['name' => 'Falkland Island Pound', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'FJD' => ['name' => 'Fijian dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'GMD' => ['name' => 'Gambian dalasi', 'symbol' => '', 'decimals' => 2, 'is_crypto' => false], 
        'GEL' => ['name' => 'Georgian lari', 'symbol' => 'GEL', 'decimals' => 2, 'is_crypto' => false], 
        'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'decimals' => 2, 'is_crypto' => false], 
        'GIP' => ['name' => 'Gibraltar pound', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'GTQ' => ['name' => 'Guatemalan Quetzal', 'symbol' => 'GTQ', 'decimals' => 2, 'is_crypto' => false], 
        'GNF' => ['name' => 'Guinean Franc', 'symbol' => 'FG', 'decimals' => 2, 'is_crypto' => false], 
        'GYD' => ['name' => 'Guyanese dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'HTG' => ['name' => 'Haitian gourde', 'symbol' => 'G', 'decimals' => 2, 'is_crypto' => false], 
        'HNL' => ['name' => 'Honduran lempira', 'symbol' => 'HNL', 'decimals' => 2, 'is_crypto' => false], 
        'ISK' => ['name' => 'Icelandic Krona', 'symbol' => 'Ikr', 'decimals' => 2, 'is_crypto' => false], 
        'IRR' => ['name' => 'Iranian Rial', 'symbol' => 'IRR', 'decimals' => 2, 'is_crypto' => false], 
        'IQD' => ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'decimals' => 2, 'is_crypto' => false], 
        'JMD' => ['name' => 'Jamaican dollar', 'symbol' => 'J$', 'decimals' => 2, 'is_crypto' => false], 
        'JOD' => ['name' => 'Jordanian Dinar', 'symbol' => 'JD', 'decimals' => 2, 'is_crypto' => false], 
        'KZT' => ['name' => 'Kazakhstani tenge', 'symbol' => 'KZT', 'decimals' => 2, 'is_crypto' => false], 
        'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'Ksh', 'decimals' => 2, 'is_crypto' => false], 
        'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'KD', 'decimals' => 2, 'is_crypto' => false], 
        'KGS' => ['name' => 'Kyrgyzstani som', 'symbol' => '??', 'decimals' => 2, 'is_crypto' => false], 
        'LAK' => ['name' => 'Lao or Laotian Kip', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'LBP' => ['name' => 'Lebanese Pound', 'symbol' => 'LB£', 'decimals' => 2, 'is_crypto' => false], 
        'LSL' => ['name' => 'Lesotho loti', 'symbol' => 'L or M', 'decimals' => 2, 'is_crypto' => false], 
        'LRD' => ['name' => 'Liberian Dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'LYD' => ['name' => 'Libyan Dinar', 'symbol' => 'LD', 'decimals' => 2, 'is_crypto' => false], 
        'LTL' => ['name' => 'Lithuanian litas', 'symbol' => 'Lt', 'decimals' => 2, 'is_crypto' => false], 
        'MOP' => ['name' => 'Macau Pataca', 'symbol' => 'MOP$', 'decimals' => 2, 'is_crypto' => false], 
        'MKD' => ['name' => 'Macedonian Denar', 'symbol' => 'MKD', 'decimals' => 2, 'is_crypto' => false], 
        'MGA' => ['name' => 'Malagasy Ariary', 'symbol' => 'MGA', 'decimals' => 2, 'is_crypto' => false], 
        'MWK' => ['name' => 'Malawian Kwacha', 'symbol' => 'MK', 'decimals' => 2, 'is_crypto' => false], 
        'MVR' => ['name' => 'Maldivian Rufiyaa', 'symbol' => 'rf', 'decimals' => 2, 'is_crypto' => false], 
        'MRO' => ['name' => 'Mauritanian Ouguiya', 'symbol' => 'UM', 'decimals' => 2, 'is_crypto' => false], 
        'MUR' => ['name' => 'Mauritian rupee', 'symbol' => 'MURs', 'decimals' => 2, 'is_crypto' => false], 
        'MDL' => ['name' => 'Moldovan Leu', 'symbol' => 'MDL', 'decimals' => 2, 'is_crypto' => false], 
        'MNT' => ['name' => 'Mongolian Tughrik', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'MAD' => ['name' => 'Moroccan Dirham', 'symbol' => 'MAD', 'decimals' => 2, 'is_crypto' => false], 
        'MZN' => ['name' => 'Mozambican Metical', 'symbol' => 'MTn', 'decimals' => 2, 'is_crypto' => false], 
        'NAD' => ['name' => 'Namibian Dollar', 'symbol' => 'N$', 'decimals' => 2, 'is_crypto' => false], 
        'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => 'NPRs', 'decimals' => 2, 'is_crypto' => false], 
        'VUV' => ['name' => 'Ni-Vanuatu Vatu', 'symbol' => 'VT', 'decimals' => 2, 'is_crypto' => false], 
        'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦', 'decimals' => 2, 'is_crypto' => false], 
        'KPW' => ['name' => 'North Korean won', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'OMR' => ['name' => 'Omani Rial', 'symbol' => 'OMR', 'decimals' => 2, 'is_crypto' => false], 
        'PGK' => ['name' => 'Papua New Guinean Kina', 'symbol' => 'K', 'decimals' => 2, 'is_crypto' => false], 
        'PYG' => ['name' => 'Paraguayan guarani', 'symbol' => '₲', 'decimals' => 2, 'is_crypto' => false], 
        'PEN' => ['name' => 'Peruvian nuevo sol', 'symbol' => 'S/.', 'decimals' => 2, 'is_crypto' => false], 
        'QAR' => ['name' => 'Qatari Riyal', 'symbol' => 'QR', 'decimals' => 2, 'is_crypto' => false], 
        'RON' => ['name' => 'Romanian leu', 'symbol' => 'RON', 'decimals' => 2, 'is_crypto' => false], 
        'WST' => ['name' => 'Samoan T?l?', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'SAR' => ['name' => 'Saudi Arabian Riyal', 'symbol' => 'SR', 'decimals' => 2, 'is_crypto' => false], 
        'RSD' => ['name' => 'Serbian Dinar', 'symbol' => 'din.', 'decimals' => 2, 'is_crypto' => false], 
        'SCR' => ['name' => 'Seychellois Rupee', 'symbol' => 'Rs', 'decimals' => 2, 'is_crypto' => false], 
        'SLL' => ['name' => 'Sierra Leonean Leone', 'symbol' => 'Le', 'decimals' => 2, 'is_crypto' => false], 
        'SBD' => ['name' => 'Solomon Islander Dollar', 'symbol' => 'SI$', 'decimals' => 2, 'is_crypto' => false], 
        'SOS' => ['name' => 'Somali Shilling', 'symbol' => 'Ssh', 'decimals' => 2, 'is_crypto' => false], 
        'SSP' => ['name' => 'South Sudanese pound', 'symbol' => '?', 'decimals' => 2, 'is_crypto' => false], 
        'LKR' => ['name' => 'Sri Lankan Rupee', 'symbol' => 'SLRs', 'decimals' => 2, 'is_crypto' => false], 
        'SDG' => ['name' => 'Sudanese Pound', 'symbol' => 'SDG', 'decimals' => 2, 'is_crypto' => false], 
        'SRD' => ['name' => 'Surinamese dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'SZL' => ['name' => 'Swazi Lilangeni', 'symbol' => 'L or E', 'decimals' => 2, 'is_crypto' => false], 
        'SYP' => ['name' => 'Syrian Pound', 'symbol' => 'SY£', 'decimals' => 2, 'is_crypto' => false], 
        'TJS' => ['name' => 'Tajikistani somoni', 'symbol' => '', 'decimals' => 2, 'is_crypto' => false], 
        'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'TSh', 'decimals' => 2, 'is_crypto' => false], 
        'TOP' => ['name' => 'Tongan Pa\'anga', 'symbol' => 'T$', 'decimals' => 2, 'is_crypto' => false], 
        'TTD' => ['name' => 'Trinidadian dollar', 'symbol' => 'TT$', 'decimals' => 2, 'is_crypto' => false], 
        'TND' => ['name' => 'Tunisian Dinar', 'symbol' => 'DT', 'decimals' => 2, 'is_crypto' => false], 
        'TMT' => ['name' => 'Turkmenistan manat', 'symbol' => 'T', 'decimals' => 2, 'is_crypto' => false], 
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'is_crypto' => false], 
        'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'USh', 'decimals' => 2, 'is_crypto' => false], 
        'UAH' => ['name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'decimals' => 2, 'is_crypto' => false], 
        'UYU' => ['name' => 'Uruguayan peso', 'symbol' => '$U', 'decimals' => 2, 'is_crypto' => false], 
        'UZS' => ['name' => 'Uzbekistani som', 'symbol' => 'UZS', 'decimals' => 2, 'is_crypto' => false], 
        'VEF' => ['name' => 'Venezuelan bolivar', 'symbol' => 'Bs.F.', 'decimals' => 2, 'is_crypto' => false], 
        'VND' => ['name' => 'Vietnamese Dong', 'symbol' => '₫', 'decimals' => 2, 'is_crypto' => false], 
        'YER' => ['name' => 'Yemeni Rial', 'symbol' => 'YR', 'decimals' => 2, 'is_crypto' => false], 
        'ZMW' => ['name' => 'Zambian Kwacha', 'symbol' => 'ZMK', 'decimals' => 2, 'is_crypto' => false], 
        'ZWD' => ['name' => 'Zimbabwean Dollar', 'symbol' => 'Z$', 'decimals' => 2, 'is_crypto' => false]
    ];

}
 
