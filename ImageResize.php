<?php

    /**
     * Class ImageResize
     *
     * Simple image resizing.
     * Supported image types are: JPG/JPEG, PNG, GIF.
     * You can control whether the image will be enlarged or not if it is smaller.
     * You can set whether or not the image is cropped to meet the new measurements.
     * By default it does not enlarge if it is smaller and does not trim, respecting the maximum measures.
     *
     * @author      Daniel P. Jr <danielpjr80@gmail.com>
     * @license     GPL
     * @license     http://opensource.org/licenses/gpl-license.php GNU Public License
     * @version     0.01
     *
     * @example     // Resize respecting sizes.
     *              ImageResize::source( 'path/to/source/image.jpg' )
     *                   ->size( 100, 100 )
     *                   ->save( 'path/to/destination/image.jpg' );
     *
     *              // Resizes forcing sizes.
     *              ImageResize::source( 'path/to/source/image.jpg' )
     *                   ->size( 100, 100, true )
     *                   ->save( 'path/to/destination/image.jpg' );
     *
     *              // Resizes forcing quality.
     *              ImageResize::source( 'path/to/source/image.jpg' )
     *                   ->size( 100, 100 )
     *                   ->save( 'path/to/destination/image.jpg', 100 );
     *
     *              // Multiple resize of an image.
     *              ImageResize::source( 'path/to/source/image.jpg' )
     *                   ->size( 100, 100 )->save( 'path/to/destination/image1.jpg' )
     *                   ->size( 450, 300 )->save( 'path/to/destination/image2.jpg' )
     *                   ->size( 800, 600 )->save( 'path/to/destination/image3.jpg' );
     *
     *              // Multiple source image.
     *              ImageResize::source( 'path/to/source/image1.jpg' )
     *                   ->size( 100, 100 )->save( 'path/to/destination/image1.jpg' )
     *                   ->source( 'path/to/source/image2.jpg' )
     *                   ->size( 800, 600 )->save( 'path/to/destination/image3.jpg' );
     *
     *              // Resizes and remove source image.
     *              ImageResize::source( 'path/to/source/image.jpg' )
     *                   ->size( 100, 100 )
     *                   ->save( 'path/to/destination/image.jpg' );
     *                   ->clear();
     */
    class ImageResize
    {
        /**
         * Full or relative path source image
         *
         * @access private
         * @var string
         */
        private $source = '';

        /**
         * Full or relative path destination image.
         *
         * @access private
         * @var string
         */
        private $dest = '';
        private $maxWidth = 0;
        private $maxHeight = 0;

        /**
         * The pattern to search for validate supported image formats.
         *
         * @access private
         * @var string
         */
        private $imageTypesPatern = '/(.jpe?g|.gif|.png)$/i';

        /**
         * The pattern to search for validate image name and path.
         *
         * @access private
         * @var string
         */
        private $imageNamePattern = '/[^a-zA-Z0-9\.\/\-_:]/';

        /**
         * Holds eventual error plain message.
         *
         * @access public
         * @var array
         */
        public $errors = array();

        /**
         * Internal class variable that informs the need to crop the image.
         *
         * @access private
         * @var bool
         */
        private $crop = false;

        /**
         * Internal class variable that informs the need to resize the image.
         *
         * @access private
         * @var bool
         */
        private $resize = false;

        /**
         * It informs if the class should force the resizing and / or cropping of the image
         * to respect the new measures informed by the user.
         *
         * @access private
         * @var bool
         */
        private $forceSize = false;

        /**
         * quality is optional, and ranges from 0 (worst quality, smaller file)
         * to 100 (best quality, biggest file).
         * The default is the default IJG quality value (about 75).
         *
         * @access private
         * @var int
         */
        private $quality = 75;

        /**
         * Image resource identifier from imagecreatefrom{jpeg|gif|png}().
         *
         * @access private
         * @var resource
         */
        private $src_image = null;

        /**
         * Image resource identifier from imagecreatetruecolor().
         *
         * @access private
         * @var resource
         */
        private $dst_image = null;

        /**
         * Image resource identifier from imagecreatetruecolor().
         *
         * @access private
         * @var resource
         */
        private $dst_cropped = null;

        /**
         * Image resource identifier from $this->dst_image or $this->dst_cropped.
         *
         * @access private
         * @var resource
         */
        private $dst_final = null;

        /**
         * X-coordinate of source point.
         *
         * Used only when there is a need to crop the image
         *
         * @access private
         * @var int
         */
        private $src_x = 0;

        /**
         * Y-coordinate of source point.
         *
         * Used only when there is a need to crop the image
         *
         * @access private
         * @var int
         */
        private $src_y = 0;

        /**
         * Destination width.
         *
         * @access private
         * @var int
         */
        private $dst_w = 0;

        /**
         * Destination height.
         *
         * @access private
         * @var int
         */
        private $dst_h = 0;

        /**
         * Source width.
         *
         * @access private
         * @var int
         */
        private $src_w = 0;

        /**
         * Source height.
         *
         * @access private
         * @var int
         */
        private $src_h = 0;

        /**
         * ImageResize constructor.
         *
         * @param string $source
         *
         * @access public
         */
        public function __construct( $source = '')
        {
            set_error_handler( array($this, 'customErrorHandler') );

            $this->size();

            if( false == empty($source) )
            {
                $source = preg_replace( $this->imageNamePattern , '' , $source );

                if( false == preg_match( $this->imageTypesPatern , $source ) )
                {
                    $this->errors[] = "Source: [{$source}] is not a valid type.";

                    return $this;
                }

                if( false == is_file( $source ) )
                {
                    $this->errors[] = "Source: [{$source}] is not a file or does not have a valid name.";

                    return $this;
                }

                if( false == is_readable( $source ) )
                {
                    $this->errors[] = "Source: [{$source}] can not be read.";

                    return $this;
                }

                list($this->src_w , $this->src_h) = getimagesize( $source );

                if( !$this->src_w || !$this->src_h )
                {
                    $this->errors[] = "Width and Height of source are invalid: Width[{$this->src_w}], Height[{$this->src_h}].";

                    $this->src_w = $this->src_h = 0;

                    return $this;
                }

                $this->source = $source;
            }

            return $this;

        }// __construct()

        /**
         * It defines the measures of the new image and whether these measures must be respected.
         *
         * @param int $maxWidth
         * @param int $maxHeight
         * @param bool $forceSize
         *
         * @access public
         * @return $this
         */
        public function size( $maxWidth = 0 , $maxHeight = 0, $forceSize = false )
        {
            $maxWidth = intval( $maxWidth );
            $maxHeight = intval( $maxHeight );

            $this->maxWidth = ( $maxWidth ) ? $maxWidth : 1200;
            $this->maxHeight = ( $maxHeight ) ? $maxHeight : 800;

            $this->forceSize = $forceSize;

            return $this;
        }

        /**
         * Validate the source image and get your measurements.
         *
         * @param string $source
         *
         * @access public
         * @return $this
         */
        public static function source( $source = '' )
        {
            return new ImageResize( $source );

        }// source()

        /**
         * Calculate the new measures.
         *
         * Defines whether to resize and or trim.
         *
         * @access private
         * @return $this
         */
        private function calculate()
        {
            // Do not resize and or crop.

            if( $this->maxWidth > $this->src_w || $this->maxHeight > $this->src_h )
            {
                if( false == $this->forceSize )
                {
                    $this->resize = false;

                    return $this;
                }
            }

            $this->resize = true;

            $this->dst_w = $this->maxWidth;
            $this->dst_h = intval( $this->src_h * ($this->maxWidth / $this->src_w) );

            if( ($this->dst_h < $this->maxHeight && $this->forceSize)
             || ($this->dst_h > $this->maxHeight && false == $this->forceSize) )
            {
                $this->dst_h = $this->maxHeight;
                $this->dst_w = intval( $this->src_w * ($this->maxHeight / $this->src_h) );
            }

            $this->crop = ( $this->dst_w > $this->maxWidth || $this->dst_h > $this->maxHeight );

            if( $this->crop )
            {
                // Define X and Y positions where copying of the already resized image will begin.

                $this->src_x = intval( ($this->dst_w - $this->maxWidth) / 2 );
                $this->src_x = ($this->src_x > 0) ? $this->src_x : 0;

                $this->src_y = intval( ($this->dst_h - $this->maxHeight) / 2 );
                $this->src_y = ($this->src_y > 0) ? $this->src_y : 0;
            }

        }// calculate()

        /**
         * Create final image.
         *
         * Define image quality.
         *
         * Validate any image extensions.
         *
         * @param string $dest
         * @param null $quality
         *
         * @access public
         * @return $this
         *
         * @todo Validate path if it exists.
         */
        public function save( $dest = '', $quality = null )
        {
            $this->quality = ($quality) ? $quality : $this->quality;

            $this->dest = preg_replace( $this->imageNamePattern, '', strtolower($dest) );

            $extOrigem = explode( '.', $this->source );
            $extOrigem = strtolower( end( $extOrigem ) );

            $extDest = explode( '.', $this->dest );
            $extDest = end( $extDest );

            if( $extOrigem != $extDest )
            {
                if( preg_match('/^(jpe?g)$/', $extOrigem) && false == preg_match('/^(jpe?g)$/', $extDest) )
                {
                    $this->errors[] = "Destination: [{$this-> dest}] is not the same type as Origin: [{$this->source}].";

                    return $this;
                }
            }

            if( false == preg_match( $this->imageTypesPatern, $this->dest ) )
            {
                $this->errors[] = "Destination: [{$this->dest} ]not a valid type.";

                return $this;
            }

            $this->calculate();

            if( $this->resize )
            {
                switch( $extOrigem )
                {
                    case 'gif':
                        $this->src_image = imagecreatefromgif( $this->source );
                        break;

                    case 'jpeg':
                    case 'jpg':
                        $this->src_image = imagecreatefromjpeg( $this->source );
                        break;

                    case 'png':
                        $this->src_image = imagecreatefrompng( $this->source );
                        break;
                }

                if( false == $this->src_image )
                {
                    $this->errors[] = "Could not create from Source: [{$this->source}].";

                    return $this;
                }

                $this->dst_image = imagecreatetruecolor( $this->dst_w, $this->dst_h );

                if( false == $this->dst_image )
                {
                    $this->errors[] = "Imagetruecolor failed for resized image.";

                    return $this;
                }

                if( false == imagecopyresampled ( $this->dst_image , $this->src_image , 0, 0, 0, 0 , $this->dst_w , $this->dst_h , $this->src_w , $this->src_h ) )
                {
                    $this->errors[] = "imagecopyresampled failed for resized image.";

                    return $this;
                }

                $this->dst_final = $this->dst_image;

                if( $this->crop )
                {
                    $this->dst_cropped = imagecreatetruecolor( $this->maxWidth, $this->maxHeight );

                    if( false == $this->dst_cropped )
                    {
                        $this->errors[] = "Imagetruecolor failed for cropped image.";

                        return $this;
                    }

                    if( false == imagecopyresampled( $this->dst_cropped, $this->dst_image, 0, 0, $this->src_x, $this->src_y, $this->maxWidth, $this->maxHeight, $this->maxWidth, $this->maxHeight) )
                    {
                        $this->errors[] = "imagecopyresampled failed for cropped image.";

                        return $this;
                    }

                    $this->dst_final = $this->dst_cropped;
                }

                switch( $extDest )
                {
                    case 'gif':
                        imagegif( $this->dst_final , $this->dest, $this->quality );
                        break;

                    case 'jpeg':
                    case 'jpg':
                        imagejpeg( $this->dst_final , $this->dest, $this->quality );
                        break;

                    case 'png':
                        imagepng( $this->dst_final , $this->dest, $this->quality );
                        break;
                }
            }
            else
            {
                // Do not resize, just create a copy of the original image.

                @copy( $this->source , $this->dest );
            }

            usleep( 100000 );

            clearstatcache();

            if( false == is_file($this->dest) )
            {
                $this->errors[] = "Unable to save destination: [{$this->dest}].";
            }

            return $this;

        }// save()

        /**
         * Deletes the source image.
         *
         * Only if source is different from destination.
         *
         * @access public
         * @return $this
         */
        public function clear()
        {
            if( false == empty($this->source) && false == empty($this->dest) && ( $this->source != $this->dest ) )
            {
                @unlink( $this->source );
            }

            return $this;
        }

        /**
         * Custom error handler.
         *
         * @param $errno
         * @param $errstr
         * @param $errfile
         * @param $errline
         *
         * @access public
         */
        public function customErrorHandler( $errno , $errstr , $errfile , $errline )
        {

        }

        /**
         * Clear the resources.
         *
         * @access public
         */
        public function __destruct()
        {
            imagedestroy( $this->dst_final );
            imagedestroy( $this->dst_image );
            imagedestroy( $this->dst_cropped );
        }
    }
