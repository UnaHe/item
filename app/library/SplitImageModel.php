<?php
final class SplitImageModel {
	private $file;
	private $image;
	private $info;
	private $splitWidth;
	private $splitHeight;
	public function __construct($file , $splitWidth) {
		if (file_exists ( $file )) {
			$this->file = $file;
			
			$info = getimagesize ( $file );

			$this->info = array (
					'width' => $info [0],
					'height' => $info [1],
					'bits' => $info ['bits'],
					'mime' => $info ['mime'] 
			);
			$this->splitWidth = $splitWidth;
			$this->splitHeight = $splitWidth;
			$this->image = $this->create ( $file );
		} else {
			exit ( 'Error: Could not load image ' . $file . '!' );
		}
	}
	private function create($image) {
		$mime = $this->info ['mime'];
		if ($mime == 'image/gif') {
			return imagecreatefromgif ( $image );
		} elseif ($mime == 'image/png') {
			return imagecreatefrompng ( $image );
		} elseif ($mime == 'image/jpeg') {
			return imagecreatefromjpeg ( $image );
		}
	}
	public function split($saveDir) {
		/*
		 * bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
		 * $dst_image：新建的图片
		 * $src_image：需要载入的图片
		 * $dst_x：设定需要载入的图片在新图中的x坐标
		 * $dst_y：设定需要载入的图片在新图中的y坐标
		 * $src_x：设定载入图片要载入的区域x坐标
		 * $src_y：设定载入图片要载入的区域y坐标
		 * $dst_w：设定载入的原图的宽度（在此设置缩放）
		 * $dst_h：设定载入的原图的高度（在此设置缩放）
		 * $src_w：原图要载入的宽度
		 * $src_h：原图要载入的高度
		 */
		$baseX = 8190;
		$baseY = 8191;
		$xNumber = $this->info['width']/$this->splitWidth;
		$yNumber = $this->info['width']/$this->splitWidth/2;
		$squareNumber = $this->info['width']/512;
		$baseX *= $squareNumber;
		$baseY *= $squareNumber;

		foreach(range(1,$yNumber) as $y){
			foreach(range(1,$xNumber) as $x){
				$fileName = ($baseX + ($x-1)).'-'.($baseY + ($y-1)).'.png';
				$xpos = ($x-1)*$this->splitWidth;
				$ypos = ($y-1)*$this->splitWidth;
				$image = imagecreatetruecolor ( $this->splitWidth, $this->splitHeight );
//				if (isset ( $this->info ['mime'] ) && $this->info ['mime'] == 'image/png') {
					imagealphablending ( $image, false );
					imagesavealpha ( $image, true );
					$background = imagecolorallocatealpha ( $image, 255, 255, 255, 127 );
					imagecolortransparent ( $image, $background );
//				} else {
//					$background = imagecolorallocate ( $image, 255, 255, 255 );
//				}

				imagefilledrectangle ( $image, 0, 0, $this->splitWidth, $this->splitHeight, $background );
				imagecopyresampled ( $image, $this->image,0,0,$xpos, $ypos, $this->splitWidth, $this->splitHeight, $this->splitWidth, $this->splitHeight );
				imagepng ( $image, $saveDir.$fileName, 9 );
				imagedestroy ( $image );
			}

		}
		imagedestroy ( $this->image );
		return true;
	}
}