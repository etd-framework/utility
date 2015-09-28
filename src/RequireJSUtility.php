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
use Joomla\Application\AbstractApplication;

/**
 * Classe utilitaire pour paramétrer RequireJS
 *
 * @package EtdSolutions\Framework\Utility
 */
class RequireJSUtility {

    /**
     * Scripts JS en ligne exécutés quand le DOM est chargé.
     *
     * @var array
     */
    protected static $domReadyJs = array();

    /**
     * @var array Scripts JS en ligne exécutés dans le contexte RequireJS.
     */
    protected static $requireJS = array();

    /**
     * @var array Les modules et leur chemin à charger dans RequireJS.
     */
    protected static $requireModules = array();

    /**
     * @var array Les packages à charger dans RequireJS.
     */
    protected static $requirePackages = array(
        [
            "name"     => "etdsolutions",
            "location" => "js/etdsolutions",
            "main"     => "app"
        ]
    );

    /**
     * @var array Les packages à charger dans RequireJS.
     */
    protected static $requireMap = array(
        '*' => [
            'css' => 'js/vendor/css.min'
        ]
    );

    protected static $strings = array();

    /**
     * Translate a string into the current language and stores it in the JavaScript language store.
     *
     * @param   string   $string                The string to translate.
     * @param   array    $parameters            Array of parameters for the string
     * @param   boolean  $jsSafe                True to escape the string for JavaScript output
     * @param   boolean  $interpretBackSlashes  To interpret backslashes (\\=\, \n=carriage return, \t=tabulation)
     *
     * @return  array
     */
    public function script($string = null, $parameters = [], $jsSafe = true, $interpretBackSlashes = true) {

        // Add the string to the array if not null.
        if ($string !== null)
        {

            $text = (new LanguageFactory())->getText();
            self::$strings[strtoupper($string)] = $text->translate($string, $parameters, $jsSafe, $interpretBackSlashes);
        }

        return self::$strings;
    }

    /**
     * Ajoute du script JavaScript en ligne exécuté dans le contexte jQuery.
     * Il sera exécuté après que le DOM du document soit prêt.
     *
     * @param string $script  Le script JS à ajouter.
     * @param bool   $onTop   Place le script en haut de la pile.
     * @param string $modules Des modules additionnels à charger par RequireJS.
     *
     * @return RequireJSUtility
     *
     */
    public function addDomReadyJS($script, $onTop = false, $modules = '') {

        $module = "jquery";

        if (!empty($modules)) {
            $module .= ", " . $modules;
        }

        $module .= ", domReady!";

        $this->addRequireJSModule('domReady', 'js/vendor/domReady');
        $this->requireJS($module, $script, $onTop);

        return $this;

    }

    public function addRequireJSModule($module, $path, $shim = false, $deps = null, $exports = null, $init = null) {

        if (!isset(self::$requireModules[$module])) {
            self::$requireModules[$module] = array();
        }

        self::$requireModules[$module]['module'] = $module;
        self::$requireModules[$module]['path']   = $path;
        self::$requireModules[$module]['shim']   = false;

        if ($shim) {
            $shim = array();
            if (isset($deps)) {
                $shim["deps"] = $deps;
            }
            if (isset($exports)) {
                $shim["exports"] = $exports;
            }
            if (isset($init)) {
                $shim["init"] = $init;
            }
            self::$requireModules[$module]['shim'] = $shim;
        }

        return $this;

    }

    public function addRequirePackage($package, $location = null, $main = null) {

        $package = strtolower($package);

        if (!array_key_exists($package, self::$requirePackages)) {

            if (isset($location) || isset($main)) {

                $arr = [
                    "name" => $package
                ];

                if (isset($location)) {
                    $arr["location"] = $location;
                }

                if (isset($main)) {
                    $arr["main"] = $main;
                }

            } else {
                $arr = $package;
            }

            self::$requirePackages[$package] = $arr;
        }

        return $this;
    }

    public function addRequireMap($prefix, $old, $new) {

        if (!array_key_exists($prefix, self::$requireMap)) {
            self::$requireMap[$prefix] = [];
        }

        self::$requireMap[$prefix][$old] = $new;

        return $this;
    }

    /**
     * Ajoute du JavaScript en ligne exécuté dans le contexte RequireJS.
     * Il sera exécuté après que le DOM du document soit prêt.
     *
     * @param string $module
     * @param string $script Le script JS à ajouter.
     * @param bool   $onTop  Place le script en haut de la pile.
     *
     * @return RequireJSUtility
     */
    public function requireJS($module, $script = '', $onTop = false) {

        if (strpos($module, ' ') !== false) {
            $module = str_replace(' ', '', $module);
        }

        if (!isset(self::$requireJS[$module])) {
            self::$requireJS[$module] = array();
        }

        if (!in_array($script, self::$requireJS[$module])) {
            if ($onTop) {
                array_unshift(self::$requireJS[$module], $script);
            } else {
                array_push(self::$requireJS[$module], $script);
            }
        }

        return $this;
    }

    /**
     * Effectue le rendu de la configuration RequireJS ainsi que des appels aux modules.
     *
     * @param AbstractApplication $app
     *
     * @return string
     */
    public function printRequireJS(AbstractApplication $app) {

        $js = "";

        // On ajoute les trads.
        if (count(self::$strings)) {
            $this->requireJS("etdsolutions/text", "text.load(" . json_encode(self::$strings) . ")", true);
        }

        // On crée la configuration de requireJS
        $js .= "requirejs.config({\n";
        $js .= "\tbaseUrl: '" . $app->get('uri.base.full') . "',\n";

        // Debug => cache bust
        if ($app->get('debug', false)) {
            $js .= "\turlArgs: 'bust=' +  (new Date()).getTime(),\n";
        }

        // map
        $js .= "\tmap: " . json_encode(self::$requireMap);

        // packages
        if (count(self::$requirePackages)) {
            $js .= ",\n\tpackages: " . json_encode(array_values(self::$requirePackages));
        }

        // modules
        if (count(self::$requireModules)) {

            $shim  = array();
            $paths = array();
            foreach (self::$requireModules as $module) {
                $paths[] = "\t\t" . json_encode($module['module']) . ": " . json_encode($module['path']);
                if ($module['shim'] !== false) {
                    $shim[] = "\t\t" . json_encode($module['module']) . ": " . json_encode($module['shim']);
                }
            }

            if (count($shim)) {
                $js .= ",\n\tshim: {\n";
                $js .= implode(",\n", $shim) . "\n";
                $js .= "\t}";
            }
            if (count($paths)) {
                $js .= ",\n\tpaths: {\n";
                $js .= implode(",\n", $paths) . "\n";
                $js .= "\t}";
            }

        }

        $js .= "\n});\n";

        if (count(self::$requireJS)) {

            foreach (self::$requireJS as $id => $scripts) {

                $content = "";
                $modules = explode(",", $id);

                foreach ($scripts as $script) {
                    if (!empty($script)) {
                        $content .= "  " . $script . "\n";
                    }
                }

                $js .= "require(" . json_encode($modules);

                if (!empty($content)) {
                    $modules = array_filter($modules, function ($module) {

                        return (strpos($module, '!') === false);
                    });
                    $modules = array_map(function ($module) {

                        if (strpos($module, '/') !== false) {
                            $module = substr($module, strrpos($module, '/') + 1);
                        }

                        if (strpos($module, '.min') !== false) {
                            $module = str_replace('.min', '', $module);
                        }

                        if (strpos($module, '.') !== false) {
                            $module = substr($module, strrpos($module, '.') + 1);
                        }

                        $module = str_replace('-', '', $module);

                        return $module;
                    }, $modules);
                    $js .= ", function(" . implode(",", $modules) . ") {\n";
                    $js .= $content;
                    $js .= "}";
                }

                $js .= ");\n";

            }
        }

        return $js;

    }

}