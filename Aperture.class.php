<?php
class Aperture {
	private $screen;
	private $colors;
	private $menu;
	private $data;
	private $name;

	public function __construct($name)
	{
		$this->name = $name;
		$this->screen = imagecreate(320, 200);
	}

	public function __destruct()
	{
		imagedestroy($this->screen);
	}

	private function menuFileName()
	{
		return $this->name.'.AMF';
	}

	private function imageFileName()
	{
		return $this->name.'.APF';
	}

	public function parseMenu()
	{
		if(!file_exists($this->menuFileName()))
			throw new Exception('Menu file not found');

		$handle = fopen($this->menuFileName(), 'r');

		$header = Aperture::_fnxtln($handle);
		if($header != 'APERTURE MENU FORMAT (c) 1985')
		{
			fclose($handle);
			throw new Exception('Invalid menu format');
		}

		sscanf(Aperture::_fnxtln($handle), '%d,%d,%d', $bgcolor, $fgcolor, $dwcolor);

		$bg = Aperture::_color($bgcolor);
		$this->colors['bg'] = imagecolorallocate($this->screen, $bg['r'], $bg['g'], $bg['b']);
		$fg = Aperture::_color($fgcolor);
		$this->colors['fg'] = imagecolorallocate($this->screen, $fg['r'], $fg['g'], $fg['b']);
		$dw = Aperture::_color($dwcolor);
		$this->colors['dw'] = imagecolorallocate($this->screen, $dw['r'], $dw['g'], $dw['b']);

		$this->menu = array();
		for($n = 1; $n <= 9; $n++)
		{
			if(feof($handle))
				break;

			$line = Aperture::_fnxtln($handle);
			preg_match('/([0-9]*),([0-9]*),"(.*)","(.*)"/', $line, $parts);
			if(!empty($parts[1]) || !empty($parts[2]) || !empty($parts[3]) || !empty($parts[4]))
			{
				$this->menu[$n] = array('x' => $parts[1], 'y' => $parts[2], 'label' => $parts[3], 'action' => $parts[4]);
			}
		}
	}

	public function parseImage()
	{
		if(!file_exists($this->imageFileName()))
			throw new Exception('Image file not found');

		$handle = fopen($this->imageFileName(), 'r');

		$header = Aperture::_fnxtln($handle);
		if($header != 'APERTURE IMAGE FORMAT (c) 1985')
		{
			fclose($handle);
			throw new Exception('Invalid image format');
		}

		imagefill($this->screen, 1, 1, $this->colors['bg']);

		$this->skip = Aperture::_fnxtln($handle);
		$this->data = Aperture::_fnxtln($handle);
	}

	public function parse()
	{
		$this->parseMenu();
		$this->parseImage();
	}

	public function drawImage()
	{
		$write = 1;
		$sn = 0;
		$x = 0;
		$y = 199;
		$lenght = strlen($this->data);
		for($pos = 0; $pos < $lenght; $pos++)
		{
			$num = ord($this->data[$pos]) - 32;

			$write = ($write == 1 ? 0 : 1);

			while($num > 0)
			{
				if($write)
				{
					if(($x + $num) > 320)
					{
						imageline($this->screen, $x, $y, 320, $y, $this->colors['dw']);
						$num = ($num+$x) % 320;
						$x = 0;
						$y -= $this->skip;
					}
					else
					{
						imageline($this->screen, $x, $y, ($x+$num-1), $y, $this->colors['dw']);
						$x += $num;
						$num = 0;
					}
				}
				else
				{
					if(($x + $num) > 320)
					{
						$num = ($num+$x) % 320;
						$x = 0;
						$y -= $this->skip;
					}
					else
					{
						$x += $num;
						$num = 0;
					}
				}
				if($y < 0)
				{
					$sn++;
					$y = 199-$sn;
				}
			}
		}
	}

	public function drawMenu()
	{
		foreach($this->menu as $id => $item)
		{
			$pos = Aperture::_locate($item['x'],  $item['y']);

			$text = $id.': '.$item['label'];

			$x1 = $pos['x']-8;
			$y1 = $pos['y']-9;
			$x2 = $pos['x'] + (strlen($text) * 8) + 8;
			$y2 = $pos['y']+2;

			imagefilledrectangle($this->screen, $x1, $y1, $x2, $y2, $this->colors['dw']);
			imagestring($this->screen, 3, $pos['x'], ($y1-1), $text, $this->colors['fg']);
		}
	}

	public function draw()
	{
		$this->drawMenu();
		$this->drawImage();
	}

	public function getMenu()
	{
		$menu = array();
		foreach($this->menu as $id => $item)
		{
			$menu[] = array('label' => $id.': '.$item['label'], 'action' => $item['action']);
		}
		return $menu;
	}

	public function showPNG()
	{
		header("Content-type: image/png");
		imagepng($this->screen);
	}

	public function savePNG($file)
	{
		imagepng($this->screen, $file);
	}

	/*
	 * Since we are not using the console,
	 * we need a function to translate the console "location" into x,y coordinates
	 */
	static function _locate($x, $y)
	{
		return array('x' => ($x*8), 'y' => ($y*8));
	}

	/*
	 * We could use an array for this, but until we have a complete color table, i prefer the switch
	 * Data from http://en.wikibooks.org/wiki/QBasic/Text_Output#Color_by_Number
	 * Actual color id is this + 1
	 * 0 Black
	 * 1 Blue
	 * 2 Green
	 * 3 Sky Blue
	 * 4 Red
	 * 5 Purple
	 * 6 Brown/Orange
	 * 7 Light Grey (White)
	 * 8 Dark Grey (Light Black)
	 * 9 Light Blue
	 * 10 Light Green
	 * 11 Light Sky Blue
	 * 12 Light Red
	 * 13 Light Purple
	 * 14 Yellow (Light Orange)
	 * 15 White (Light White)
	 */
	static function _color($num)
	{
		switch($num)
		{
		case 0:
			return array('r' => 0x00, 'g' => 0x00, 'b' => 0x00);
		case 1:
			return array('r' => 0xa9, 'g' => 0xa9, 'b' => 0xa9);
		case 2:
			return array('r' => 0xff, 'g' => 0x54, 'b' => 0x54);
		case 3:
			return array('r' => 0x08, 'g' => 0x06, 'b' => 0xa8);
		case 12:
			return array('r' => 0xa9, 'g' => 0x01, 'b' => 0x00);
		case 16:
		default:
			return array('r' => 0xff, 'g' => 0xff, 'b' => 0xff);
		}
	}

	/*
	 * This was made to read the next non empty line from a file
	 */
	static function _fnxtln($handle)
	{
		while(true)
		{
			$line = trim(fgets($handle));
			if(!empty($line))
				return $line;
			if(feof($handle))
				return false;
		}
	}
}
?>