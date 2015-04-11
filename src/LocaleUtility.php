<?php
/**
 * Part of the ETD Framework Utility Package
 *
 * @copyright   Copyright (C) 2015 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Utility;

use EtdSolutions\Language\LanguageFactory;
use Joomla\Language\Language;

class LocaleUtility {

    /**
     * @var Language L'objet langue.
     */
    private $lang;

    function __construct() {

        $this->lang = (new LanguageFactory)->getLanguage();

    }

    public function money_format($number, $format = '%!i') {

        setlocale(LC_MONETARY, $this->lang->getLocale());
        return money_format($format, $number);

    }

}