<?php
/**
 * Helper class for SWG slideshow module
 * 
 */
class ModSWG_SlideshowHelper
{
	/**
	 * Store the time at construction to avoid issues loading at page at midnight on a change date
	 * @var int
	 */
	private $month;
	
	const PATH_PREFIX = "/images/homepagephotos/";
	
	const PATH_SPRING = "spring";
	const PATH_SUMMER = "summer";
	const PATH_AUTUMN = "autumn";
	const PATH_WINTER = "winter";
	
	public function __construct()
	{
		$this->month = (int)date("n");
	}
	
    /**
     * Retrieves the starting photo
     */    
    public function getStartImage()
    {
        $images = $this->getImages();
        $imageId = rand(0, count($images)-1);
        return array("index" => $imageId, "image" => $images[$imageId]);
    }
    
    public function getImages()
    {
		// TODO: Caption from EXIF?
		$path = self::PATH_PREFIX;
		if ($this->isSpring()) {
			$path .= self::PATH_SPRING;
		} else if ($this->isSummer()) {
			$path .= self::PATH_SUMMER;
		} else if ($this->isAutumn()) {
			$path .= self::PATH_AUTUMN;
		} else {
			$path .= self::PATH_WINTER;
		}
		
		$d = new DirectoryIterator(JPATH_SITE.$path);
		$files = array();
		foreach ($d as $file) {
			if (!$this->isValidImage($file))
				continue;
			
			$files[] = $path."/".$file->getFilename();
		}
		return $files;
    }
    
    private function isValidImage(DirectoryIterator $file)
    {
		if (!$file->isFile())
			return false;
		
		$ext = strtolower($file->getExtension());
		
		return ($ext == "jpg" || $ext == "jpeg");
    }
		
    private function isSpring()
    {
		return ($this->month >= 3 && $this->month <= 5);
    }
    
    private function isSummer()
    {
		return ($this->month >= 6 && $this->month <= 8);
    }
    
    private function isAutumn()
    {
		return ($this->month >= 9 && $this->month <= 11);
    }
    
    private function isWinter()
    {
		return ($this->month == 12 || $this->month <= 2);
    }
}