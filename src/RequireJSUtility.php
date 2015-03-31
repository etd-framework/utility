<?php
/**
 * Part of the ETD Framework Utility Package
 *
 * @copyright   Copyright (C) 2015 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Utility;

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
        "js/etdsolutions"
    );

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

    public function addRequirePackage($package) {

        if (!in_array($package, self::$requirePackages)) {
            self::$requirePackages[] = strtolower($package);
        }

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

        // On crée la configuration de requireJS
        $js .= "requirejs.config({\n";
        $js .= "\tbaseUrl: '" . $app->get('uri.base.full') . "',\n";

        // require-css
        $js .= "\tmap: {\n";
        $js .= "\t\t'*': {\n";
        $js .= "\t\t\t'css': 'js/vendor/css.min'\n";
        $js .= "\t\t}\n";
        $js .= "\t}";

        // packages
        if (count(self::$requirePackages)) {
            $js .= ",\n\tpackages: " . json_encode(self::$requirePackages);
        }

        // modules
        if (count(self::$requireModules)) {

            $shim  = array();
            $paths = array();
            foreach (self::$requireModules as $module) {
                $paths[] = "\t\t" . $module['module'] . ": '" . $module['path'] . "'";
                if ($module['shim'] !== false) {
                    $shim[] = "\t\t" . $module['module'] . ": " . json_encode($module['shim']);
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