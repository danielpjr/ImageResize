# ImageResize
Simple Image Resize and Crop

Supported image types are: JPG/JPEG, PNG, GIF.

You can control whether the image will be enlarged or not if it is smaller.

You can set whether or not the image is cropped to meet the new measurements.

By default it does not enlarge if it is smaller and does not trim, respecting the maximum measures.

# Installation
  include 'ImageResize.php';
  
# Usage
  // Resize respecting sizes.
  
  ImageResize::source( 'path/to/source/image.jpg' )
  
       ->size( 100, 100 )
       
       ->save( 'path/to/destination/image.jpg' );
       
	   
  // Resizes forcing sizes.
  
  ImageResize::source( 'path/to/source/image.jpg' )
  
       ->size( 100, 100, true )
       
       ->save( 'path/to/destination/image.jpg' )
       
	   
  // Resizes forcing quality.
  
  ImageResize::source( 'path/to/source/image.jpg' )
  
       ->size( 100, 100 )
       
       ->save( 'path/to/destination/image.jpg', 100 )
       
	   
  // Multiple resize of an image.
  
  ImageResize::source( 'path/to/source/image.jpg' )
  
       ->size( 100, 100 )->save( 'path/to/destination/image1.jpg' )
       
       ->size( 450, 300 )->save( 'path/to/destination/image2.jpg' )
       
       ->size( 800, 600 )->save( 'path/to/destination/image3.jpg' );
       
	   
  // Multiple source image.
  
  ImageResize::source( 'path/to/source/image1.jpg' )
  
       ->size( 100, 100 )->save( 'path/to/destination/image1.jpg' )
       
       ->source( 'path/to/source/image2.jpg' )
       
       ->size( 800, 600 )->save( 'path/to/destination/image3.jpg' );
       
	   
  // Resizes and remove source image.
  
  ImageResize::source( 'path/to/source/image.jpg' )
  
       ->size( 100, 100 )
       
       ->save( 'path/to/destination/image.jpg' )
       
       ->clear();
