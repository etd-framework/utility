<?php
/**
 * Part of the ETD Framework Utility Package
 *
 * @copyright   Copyright (C) 2015 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Utility;

use Joomla\Registry\Registry;

/**
 * Classe utilitaire pour traiter les prix
 */
class PriceUtility {

    const ROUND_UP = 1;
    const ROUND_DOWN = 2;
    const ROUND_HALF = 3;

    /**
     * @var Registry Configuration de l'application.
     */
    private $config;

    function __construct(Registry $config) {

        $this->config = $config;

    }

    /**
     * Retourne une valeur arrondie à la précision spécifiée suivant la configuration.
     *
     * @param float $value     La valeur
     * @param int   $precision Le nombre de chiffres après la virgule.
     *
     * @return float La valeur arrondie
     */
    public function round($value, $precision = null) {

        $method    = $this->config->get('price.round_mode');
        $precision = isset($precision) ? (int)$precision : $this->config->get('price.default_precision');

        if ($method == self::ROUND_UP) {
            return self::ceilf($value, $precision);
        } elseif ($method == self::ROUND_DOWN) {
            return self::floorf($value, $precision);
        }

        return round($value, $precision);
    }

    /**
     * Retourne la valeur à la précision en dessous de $value.
     *
     * @param float $value     La valeur
     * @param int   $precision Le nombre de chiffres après la virgule.
     *
     * @return float La valeur arrondie
     */
    public function ceilf($value, $precision = null) {

        $precision        = isset($precision) ? (int)$precision : $this->config->get('price.default_precision');
        $precision_factor = $precision == 0 ? 1 : pow(10, $precision);
        $tmp              = $value * $precision_factor;
        $tmp2             = (string)$tmp;

        // Si la valeur actuelle a déjà la précision désirée.
        if (strpos($tmp2, '.') === false) {
            return ($value);
        }
        if ($tmp2[strlen($tmp2) - 1] == 0) {
            return $value;
        }

        return ceil($tmp) / $precision_factor;
    }

    /**
     * Retourne la valeur à la précision au dessus de $value.
     *
     * @param float $value     La valeur
     * @param int   $precision Le nombre de chiffres après la virgule.
     *
     * @return float La valeur arrondie
     */
    public function floorf($value, $precision = null) {

        $precision        = isset($precision) ? (int)$precision : $this->config->get('price.default_precision');
        $precision_factor = $precision == 0 ? 1 : pow(10, $precision);
        $tmp              = $value * $precision_factor;
        $tmp2             = (string)$tmp;

        // Si la valeur actuelle a déjà la précision désirée.
        if (strpos($tmp2, '.') === false) {
            return ($value);
        }
        if ($tmp2[strlen($tmp2) - 1] == 0) {
            return $value;
        }

        return floor($tmp) / $precision_factor;
    }

}