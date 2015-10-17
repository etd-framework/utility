<?php
/**
 * Part of the ETD Framework Utility Package
 *
 * @copyright   Copyright (C) 2015 ETD Solutions, SARL Etudoo. Tous droits réservés.
 * @license     Apache License 2.0; see LICENSE
 * @author      ETD Solutions http://etd-solutions.com
 */

namespace EtdSolutions\Utility;

use Joomla\Utilities\ArrayHelper;

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
        $destinationProps = ArrayHelper::fromObject($destinationProps);

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

            // On crée le nouveau chemin.
            $new_path = $path . "/" . $new_name . ".jpg";

            // On choisit la méthode
            $method = $props['method'];

            // On applique la méthode et on récupère la ressource.
            $handle = $this->$method($original_path, $new_path, $width, $height);

            // Si tout s'est bien passé.
            if ($handle === false) {
                return false;
            }

            // On applique un filtre si nécessaire.
            if (array_key_exists('filter', $props)) {
                $options =  (array_key_exists('filter_options', $props)) ? $props['filter_options'] : [];
                $this->filter($handle, $props['filter'], $options);
            }

            // On sauvegarde l'image.
            imagejpeg($handle, $new_path, 90);

            // On libère la mémoire.
            imagedestroy($handle);

        }

        return true;

    }

    protected function scale_inside($source_image_path, $thumbnail_image_path, $thumbnail_image_max_width, $thumbnail_image_max_height) {

        $source_gd_image = false;

        list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source_image_path);

        switch ($source_image_type) {
            case IMAGETYPE_GIF:
                $source_gd_image = imagecreatefromgif($source_image_path);
                break;
            case IMAGETYPE_JPEG:
                $source_gd_image = imagecreatefromjpeg($source_image_path);
                break;
            case IMAGETYPE_PNG:
                $source_gd_image = imagecreatefrompng($source_image_path);
                break;
        }

        if ($source_gd_image === false) {
            return false;
        }

        $source_aspect_ratio    = $source_image_width / $source_image_height;
        $thumbnail_aspect_ratio = $thumbnail_image_max_width / $thumbnail_image_max_height;

        if ($source_aspect_ratio > $thumbnail_aspect_ratio) {
            /*
             * Triggered when source image is wider
             */

            $temp_width = $thumbnail_image_max_width;
            $temp_height = ( int ) ($thumbnail_image_max_width / $source_aspect_ratio);
        } else {
            /*
             * Triggered otherwise (i.e. source image is similar or taller)
             */
            $temp_height = $thumbnail_image_max_height;
            $temp_width = ( int ) ($thumbnail_image_max_height * $source_aspect_ratio);
        }

        $temp_gd_image = imagecreatetruecolor($thumbnail_image_max_width, $thumbnail_image_max_height);
        $background_color = imagecolorallocate ($temp_gd_image, 255, 255, 255);
        imagefill($temp_gd_image, 0, 0, $background_color);

        $x0 = ($thumbnail_image_max_width - $temp_width) / 2;
        $y0 = ($thumbnail_image_max_height - $temp_height) / 2;

        imagecopyresampled(
            $temp_gd_image,
            $source_gd_image,
            $x0, $y0,
            0, 0,
            $temp_width, $temp_height,
            $source_image_width, $source_image_height
        );

        imagedestroy($source_gd_image);

        return $temp_gd_image;

    }

    protected function crop_inside($source_image_path, $thumbnail_image_path, $thumbnail_image_max_width, $thumbnail_image_max_height) {

        $source_gd_image = false;

        list($source_image_width, $source_image_height, $source_image_type) = getimagesize($source_image_path);

        switch ($source_image_type) {
            case IMAGETYPE_GIF:
                $source_gd_image = imagecreatefromgif($source_image_path);
                break;
            case IMAGETYPE_JPEG:
                $source_gd_image = imagecreatefromjpeg($source_image_path);
                break;
            case IMAGETYPE_PNG:
                $source_gd_image = imagecreatefrompng($source_image_path);
                break;
        }

        if ($source_gd_image === false) {
            return false;
        }

        $source_aspect_ratio = $source_image_width / $source_image_height;
        $thumbnail_aspect_ratio = $thumbnail_image_max_width / $thumbnail_image_max_height;

        if ($source_aspect_ratio > $thumbnail_aspect_ratio) {
            /*
             * Triggered when source image is wider
             */
            $temp_height = $thumbnail_image_max_height;
            $temp_width = ( int ) ($thumbnail_image_max_height * $source_aspect_ratio);
        } else {
            /*
             * Triggered otherwise (i.e. source image is similar or taller)
             */
            $temp_width = $thumbnail_image_max_width;
            $temp_height = ( int ) ($thumbnail_image_max_width / $source_aspect_ratio);
        }

        $temp_gd_image = imagecreatetruecolor($temp_width, $temp_height);

        imagecopyresampled(
            $temp_gd_image,
            $source_gd_image,
            0, 0,
            0, 0,
            $temp_width, $temp_height,
            $source_image_width, $source_image_height
        );

        /*
         * Copy cropped region from temporary image into the desired GD image
         */

        $x0 = ($temp_width - $thumbnail_image_max_width) / 2;
        $y0 = ($temp_height - $thumbnail_image_max_height) / 2;
        $desired_gdim = imagecreatetruecolor($thumbnail_image_max_width, $thumbnail_image_max_height);
        imagecopy(
            $desired_gdim,
            $temp_gd_image,
            0, 0,
            $x0, $y0,
            $thumbnail_image_max_width, $thumbnail_image_max_height
        );

        imagedestroy($source_gd_image);
        imagedestroy($temp_gd_image);

        return $desired_gdim;
    }

    /**
     * @param resource $handle
     * @param int $filter
     * @param array $options
     */
    protected function filter($handle, $filter, $options = array()) {

        switch ($filter) {

            case IMG_FILTER_GAUSSIAN_BLUR:

                // Validate that the blur iterations number exists and is an integer. Default is 1.
                if (!isset($options['iterations']) || !is_int($options['iterations'])) {
                    $options['iterations'] = 1;
                }

                // Perform the gaussian blur filter.
                for ($i=0; $i<$options['iterations']; $i++) {
                    imagefilter($handle, IMG_FILTER_GAUSSIAN_BLUR);
                }
                break;

            default:
                imagefilter($handle, $filter);
                break;
        }



    }

}