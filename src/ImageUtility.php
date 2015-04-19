<?php
/**
 * Part of the ETD Framework Utility Package
 *
 * @copyright   Copyright (C) 2015 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Utility;

use Joomla\Image\Image;

/**
 * Classe utilitaire pour effectuer des opérations sur des images.
 *
 * @package EtdSolutions\Framework\Utility
 */
class ImageUtility {

    /**
     * Permet de générer une série d'images retaillées à partir d'une image originale.
     *
     * @param string $original_path     Le chemin vers le fichier original.
     * @param array  $destinationProps  Un tableau associatif (suffixe => propriétés de l'image de destination, ex: profile => [size = >60x60, filter => IMG_FILTER_GAUSSIAN_BLUR])
     *
     * @return True en cas de succès, sinon déclenche une exception.
     */
    public function generateImageSizes($original_path, $destinationProps) {

        // On s'assure d'avoir des paramètres corrects.
        $destinationProps = (array) $destinationProps;

        // On instancie le gestionnaire d'image.
        $image = new Image($original_path);

        // On extrait le nom du fichier sans extension.
        $filename = pathinfo($original_path, PATHINFO_FILENAME);

        // On extrait le dossier.
        $path = pathinfo($original_path, PATHINFO_DIRNAME);

        // On crée les déclinaisons de taille pour l'image.
        foreach ($destinationProps as $suffix => $props) {

            // On décompose la taille en hauteur et largeur.
            list($height, $width) = explode('x', $props['size'], 2);

            // On crée le nouveau nom de fichier.
            $new_name = $filename."_".$suffix;

            // On change la couleur de fond.
            $image->filter('Backgroundfill', ['color' => '#FFFFFF']);

            // On redimensionne l'image.
            $newImage = $image->resize($width, $height, true, Image::SCALE_OUTSIDE);

            // On rogne l'image.
            $newImage->crop($width, $height, null, null, false);

            // On applique un filtre si nécessaire.
            if (array_key_exists('filter', $props)) {
                $options =  (array_key_exists('filter_options', $props)) ? $props['filter_options'] : [];
                $newImage->filter($props['filter'], $options);
            }

            // On sauvegarde l'image.
            $newImage->toFile($path . "/" . $new_name . ".png", IMAGETYPE_PNG, array(
                'quality' => 8
            ));

            // On libère la mémoire.
            $newImage->destroy();

        }

        // On libère la mémoire.
        $image->destroy();

        return true;

    }

}