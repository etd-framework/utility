<?php
/**
 * Part of the ETD Framework Utility Package
 *
 * @copyright   Copyright (C) 2015 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Utility;

use EtdSolutions\Document\Document;
use Joomla\Filesystem\Path;
use Joomla\Language\Text;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

/**
 * Classe utilitaire pour faire des rendus HTML de petites parties répétitives.
 *
 * @package EtdSolutions\Framework\Utility
 */
class HtmlUtility {

    /**
     * @var Text L'objet langue.
     */
    private $text;

    function __construct(Text $text) {

        $this->text = $text;

    }

    /**
     * Méthode pour trier une colonne dans un tableau.
     *
     * @param   string $title         Le titre de la colonne
     * @param   string $order         Le champ sur lequel le tri se fera
     * @param   string $direction     La direction actuelle
     * @param   mixed  $selected      Le tri sélectionné
     * @param   string $task          Un override optionnel de la tâche
     * @param   string $new_direction Une direction optionnelle pour la colonne
     * @param   string $tip           Un texte optionnel affiché comme infobulle au lieu de $title
     * @param   string $icon          L'icône à afficher
     * @param   string $formName      Le nom du formulaire à envoyer
     *
     * @return  string
     */
    public function sort($title, $order, $direction = 'asc', $selected = 0, $task = null, $new_direction = 'asc', $tip = '', $icon = null, $formName = 'form-admin') {

        $direction  = strtolower($direction);
        $orderIcons = array(
            'fa-toggle-up',
            'fa-toggle-down'
        );
        $index      = (int)($direction == 'desc');

        if ($order != $selected) {
            $direction = $new_direction;
        } else {
            $direction = ($direction == 'desc') ? 'asc' : 'desc';
        }

        // On crée un objet pour le passer au layout.
        $data            = new \stdClass;
        $data->order     = $order;
        $data->direction = $direction;
        $data->selected  = $selected;
        $data->task      = $task;
        $data->tip       = $tip;
        $data->title     = $title;
        $data->orderIcon = $orderIcons[$index];
        $data->icon      = $icon;
        $data->formName  = $formName;

        return self::render('sort', $data);
    }

    /**
     * Convertie deux chaines séparées en une chaine prête pour les infobulles Bootstrap.
     *
     * @param  string $title     Le titre de l'infobulle (ou deux chaines combinées avec '::').
     * @param  string $content   Le contenu de l'infobulle.
     * @param  bool   $translate Si true les textes seront passés dans Text.
     * @param  bool   $escape    Si true les textes seront passés dans htmlspecialchars.
     *
     * @return  string  L'infobulle
     */
    public function tooltipText($title = '', $content = '', $translate = true, $escape = true) {

        // Returne une chaine vide s'il n'y a pas de titre ou de contenu.
        if ($title == '' && $content == '') {
            return '';
        }

        // On passe les textes dans Text.
        if ($translate) {
            $title   = $this->text->translate($title);
            $content = $this->text->translate($content);
        }

        // On échappe les textes.
        if ($escape) {
            $title   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        }

        // On retourne seulement le contenu si on a pas de titre.
        if ($title == '') {
            return $content;
        }

        // On retourne seulement le titre si le titre et le contenu sont identiques.
        if ($title == $content) {
            return '<strong>' . $title . '</strong>';
        }

        // On retourne la chaine avec le titre et le contenu.
        if ($content != '') {
            return '<strong>' . $title . '</strong><br />' . $content;
        }

        // On retourne seulement le titre.
        return $title;
    }

    /**
     * Ajoute le script pour afficher les infobulles Bootstrap.
     */
    public function tooltip() {

        $doc = Document::getInstance();
        $doc->addDomReadyJS("$('[data-toggle=\"tooltip\"], .hasTooltip').tooltip({container:'body',html:true});", false, "bootstrap");

    }

    /**
     * Génère un icône d'état pour un élément.
     *
     * @param int    $value            La valeur de l'état
     * @param bool   $tooltips         Si true, on affiche une info-bulle avec l'état
     * @param string $tooltipPlacement Emplacement de l'info-bulle.
     * @param string $href             Valeur de l'attribut href du lien
     * @param null   $class            Classe CSS
     * @param array  $attribs          Attributs supplémentaires
     *
     * @return string
     */
    public function state($value, $tooltips = true, $tooltipPlacement = 'top', $href = '#', $class = null, $attribs = null) {

        $class = !empty($class) ? $class : '';

        if ($tooltips) {
            $class .= ' hasTooltip';
        }

        $html = '<a href="' . $href . '"';

        if (!empty($class)) {
            $html .= ' class="' . $class . '"';
        }

        if (!empty($attribs)) {
            $attribs = ArrayHelper::toString($attribs);
            $html .= ' ' . $attribs;
        }

        if ($tooltips) {

            switch ($value) {
                default:
                case 0:
                    $tooltipText = 'APP_GLOBAL_STATE_0';
                    break;
                case 1:
                    $tooltipText = 'APP_GLOBAL_STATE_1';
                    break;
                case 2:
                    $tooltipText = 'APP_GLOBAL_STATE_2';
                    break;
                case -2:
                    $tooltipText = 'APP_GLOBAL_STATE__2';
                    break;
            }

            $html .= ' data-toggle="tooltip" data-placement="' . $tooltipPlacement . '" title="' . $this->tooltipText($tooltipText) . '"';
        }

        switch ($value) {
            default:
            case 0:
                $icon = 'times';
                break;
            case 1:
                $icon = 'check';
                break;
            case 2:
                $icon = 'archive';
                break;
            case -2:
                $icon = 'trash';
                break;
        }

        $html .= '><span class="fa fa-' . $icon . '"></span></a>';

        return $html;

    }

    /**
     * Méthode pour effectuer le rendu.
     *
     * @param string $layout Le nom du layout.
     * @param object $data   Les paramètres et données à passer au layout.
     *
     * @return string Le rendu.
     * @throws \InvalidArgumentException Si le layout est introuvable.
     */
    protected function render($layout, $data) {

        // On récupère le chemin vers le layout.
        $path = Path::clean(JPATH_THEME . '/html/utility/' . $layout . '.php');

        // On contrôle que le chemin existe.
        if (!file_exists($path)) {
            throw new \InvalidArgumentException('HtmlUtility Layout Path Not Found : ' . $layout, 404);
        }

        // On crée un buffer de sortie.
        ob_start();

        // On charge le layout.
        include $path;

        // On récupère le contenu.
        $output = ob_get_clean();

        return $output;

    }

}