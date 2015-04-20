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
use Joomla\Date\Date;
use Joomla\Language\Language;

class DateUtility {

    /**
     * @var Language L'objet langue.
     */
    private $lang;

    /**
     * @var string Le fuseau horaire.
     */
    private $tz;

    function __construct($tz = '') {

        $this->lang = (new LanguageFactory)->getLanguage();
        $this->tz   = $tz;

    }

    /**
     * Méthode pour formater une date en gérant le fuseau horaire et
     * la langue choisis dans la conf de l'utilisateur.
     *
     * @param string $date   La date à formater
     * @param string $format Le format à utiliser
     *
     * @return string La date formatée
     */
    public function format($date, $format) {

        // Si ce n'est un objet Date, on le crée.
        if (!($date instanceof Date)) {
            $date = new Date($date);
        }

        // Si un fuseau horaire utilisateur est spécifié dans l'appli.
        if (!empty($this->tz)) {
            $date->setTimezone(new \DateTimeZone($this->tz));
        }

        // Si le format est une chaine traduisible (format différent suivant la langue de l'utilisateur)
        if ($this->lang->hasKey($format)) {
            $format = $this->lang->translate($format);
        }

        setlocale(LC_TIME, $this->lang->getLocale());
        return strftime($format, $date->getTimestamp());

    }

    /**
     * Méthode pour déplacer une date suivant un interval (e.g. -P7D, -P1DT1H ou +PT5H)
     *
     * @param string $date
     * @param string $interval
     *
     * @return Date
     */
    public static function moveDate($date, $interval) {

        // Si ce n'est un objet Date, on le crée.
        if (!($date instanceof Date)) {
            $date = new Date($date);
        }

        // On transforme l'intervale en format ISO.
        $iso = strtoupper(substr($interval, 1));

        // On crée l'intervale de date
        $dateint = new \DateInterval($iso);

        // On ajoute si on a un "+"
        if (strpos($interval, '+') === 0) {
            $date->add($dateint);
        } elseif (strpos($interval, '-') === 0) { // On retire si on a un "-"
            $date->sub($dateint);
        }

        return $date;

    }

}