<?php

class SearchRoadModel
{
	const DIRECTION_LEFT = 'left';
	const DIRECTION_UP = 'up';
	const DIRECTION_RIGHT = 'right';
	const DIRECTION_DOWN = 'down';
	public $closelist = array();
	public $openlist = array();
	private $gw = 10;
	private $gh = 10;
	private $gwh = 14;

	public $p_start = array();
	public $p_end = array();
	public $s_path = array();
	public $n_path = array();
	public $path = array();
	public $crossPoints = array();
	public $crossConfig;


	private $width;
	private $height;
	private $pieceWidth = 10;
	private $w = 51; //地图宽度
	private $h = 25; //地图高度

	public function __construct($width, $height, $pieceWidth)
	{
		$this->width = $width;
		$this->height = $height;
		$this->pieceWidth = $pieceWidth;
		$this->setWidth();
	}

	private function setWidth()
	{
		$this->w = floor($this->width / $this->pieceWidth);
		$this->h = floor($this->height / $this->pieceWidth);
	}

	public function setCrossConfig($crossXml)
	{
//        $crossXml = get_object_vars($crossXml);
		$this->crossConfig = $crossXml;
	}

	function isStart($pos)
	{
		if ($pos[0] == $this->p_start[0] && $pos[1] == $this->p_start[1])
			return true;
		return false;
	}

	function isOutOfMap($arr)
	{
		if ($arr[0] < 0 || $arr[1] < 0 || $arr[0] > ($this->w - 1) || $arr[1] > ($this->h - 1)) {
			return true;
		}
		return false;
	}

	function getRound($pos)
	{
		$a = array();
		$a[0] = ($pos[0] + 1) . "," . ($pos[1] - 1);
		$a[1] = ($pos[0] + 1) . "," . $pos[1];
		$a[2] = ($pos[0] + 1) . "," . ($pos[1] + 1);
		$a[3] = $pos[0] . "," . ($pos[1] + 1);
		$a[4] = ($pos[0] - 1) . "," . ($pos[1] + 1);
		$a[5] = ($pos[0] - 1) . "," . $pos[1];
		$a[6] = ($pos[0] - 1) . "," . ($pos[1] - 1);
		$a[7] = $pos[0] . "," . ($pos[1] - 1);
		return $a;
	}

	public function getF($arr)
	{
		foreach ($arr as $v) {
			$t = explode(',', $v);
			if ($this->isOutOfMap($t) || $this->isPass($v) || $this->inClose($t) || $this->isStart($t)) {
				continue;
			}
			if ((($t[0] - $this->s_path[3][0]) * ($t[1] - $this->s_path[3][1])) != 0)
				$G = $this->s_path[1] + $this->gwh;
			else
				$G = $this->s_path[1] + $this->gw;
			$openListKey = $this->inOpen($t);
			if ($openListKey) {
				if ($G < $this->openlist[$openListKey][1]) {
					$this->openlist[$openListKey][0] = ($G + $this->openlist[$openListKey][2]);
					$this->openlist[$openListKey][1] = $G;
					$this->openlist[$openListKey][4] = $this->s_path[3];
				} else {
					$G = $this->openlist[$openListKey][1];
				}
			} else {
				$H = (abs($this->p_end[0] - $t[0]) + abs($this->p_end[1] - $t[1])) * $this->gw;
				$F = $G + $H;
				$_arr = array();
				$_arr[0] = $F;
				$_arr[1] = $G;
				$_arr[2] = $H;
				$_arr[3] = $t;
				$_arr[4] = $this->s_path[3];
				$this->openlist[] = $_arr;
			}
		}
	}

	function isPass($pos)
	{
		if (in_array($pos, $this->n_path))
			return true;
		return false;
	}

	public function inOpen($arr)
	{
		$return = false;
		foreach ($this->openlist as $k => $v) {
			if (($arr[0] == $v[3][0]) && ($arr[1] == $v[3][1])) {
				$return = $k;
				break;
			}
		}
		return $return;
	}

	public function setPos($startPos = null, $endPos = null)
	{
		if (!is_null($startPos)) {
			if (!is_array($startPos)) {
				$startPos = explode(',', $startPos);
			}
			$this->p_start = $startPos;
		}
		if (!is_null($endPos)) {
			if (!is_array($endPos)) {
				$endPos = explode(',', $endPos);
			}

			$this->p_end = $endPos;
		}
		if (empty($this->p_start) || empty($this->p_end)) {
			return false;
		}
		$h = (abs($this->p_end[0] - $this->p_start[0]) + abs($this->p_end[1] - $this->p_start[1])) * $this->gw;
		$this->s_path = array($h, 0, $h, $this->p_start, $this->p_start);
	}

	function inClose($arr)
	{
		$return = false;
		foreach ($this->closelist as $v) {
			if (($arr[0] == $v[3][0]) && ($arr[1] == $v[3][1])) {
				$return = true;
				break;
			}
		}
		return $return;
	}

	function isInTurn($arr)
	{
		if ($arr[0] > $this->s_path[3][0]) {
			if ($arr[1] > $this->s_path[3][1]) {
				if ($this->isPass(($arr[0] - 1) . "," . $arr[1]) || $this->isPass($arr[0] . "," . ($arr[1] - 1)))
					return false;
			} else if ($arr[1] < $this->s_path[3][1]) {
				if ($this->isPass(($arr[0] - 1) . "," . $arr[1]) || $this->isPass($arr[0] . "," . ($arr[1] + 1)))
					return false;
			}
		} else if ($arr[0] < $this->s_path[3][0]) {
			if ($arr[1] > $this->s_path[3][1]) {
				if ($this->isPass(($arr[0] + 1) . "," . $arr[1]) || $this->isPass($arr[0] . "," . ($arr[1] - 1)))
					return false;
			} else if ($arr[1] < $this->s_path[3][1]) {
				if ($this->isPass(($arr[0] + 1) . "," . $arr[1]) || $this->isPass($arr[0] . "," . ($arr[1] + 1)))
					return false;
			}
		}
		return true;
	}

	function Sort(&$arr)
	{
		$arrCount = count($arr);
		for ($i = 0; $i < $arrCount; $i++) {
			if ($arrCount == 1) break;
			if ($arr[$i][0] <= $arr[$i + 1][0]) {
				$temp = $arr[$i];
				$arr[$i] = $arr[$i + 1];
				$arr[$i + 1] = $temp;
			}
			if (($i + 1) == ($arrCount - 1))
				break;
		}
	}

	function getPath()
	{
		$road = array();
		if (!isset($this->closelist[count($this->closelist) - 1])) {
			return array();
		}
		$t = $this->closelist[count($this->closelist) - 1][4];
		while (1) {
			$road[] = $t;
			foreach ($this->closelist as $v) {
				if ($v[3][0] == $t[0] && $v[3][1] == $t[1])
					$t = $v[4];
			}
			if ($t[0] == $this->p_start[0] && $t[1] == $this->p_start[1])
				break;
		}

//        arsort($road);
		$this->path = array_values($road);
	}

	public function pointToLatlng($point)
	{
		if (!is_array($point)) {
			$point = explode(',', $point);
		}
		$len = 0.02197;
		$width = 4 * $len;
		$col = floor($this->width / $this->pieceWidth);
		$row = floor($this->height / $this->pieceWidth);
		$pSize = ($width / $col);
		$halfWidth = $pSize * ($col / 2);
		$halfHeight = $pSize * ($row / 2);
		$pSize2 = $pSize / 2;

		$xWidth = $point[0] * $pSize + $pSize2;
		$x = $xWidth - $halfWidth;
		$yWidth = $point[1] * $pSize;
		$y = $halfHeight - $yWidth - $pSize2;
		return $y . ',' . $x;
	}

	public function getCrossPoint($startPoint, $endPoint)
	{

		$crossPoints = $newCrossPoint = array();
		$localeCrossPoints = array();
		$crossXml = $this->crossConfig;
		foreach ($crossXml['map'] as $k => $v) {
//            if ($v['mapid'] == $endPoint['map_id']) {
//                unset($crossXml['map'][$k]);
//                continue;
//            }
			foreach ($v['cango'] as $go) {
				if ($go['mapid'] == $endPoint['map_id']) {
					if ($v['mapid'] == $startPoint['map_id']) {
						$localeCrossPoints[] = $v;
					} else {
						if (!array_key_exists($v['mapid'] . $v['point'], $crossPoints)) {
							$crossPoints[$v['mapid'] . $v['point']] = $v;
						}
					}
				}
			}
		}

		if (!empty($localeCrossPoints)) {
			$crossCount = 0;
			$crossTmp = array();
			foreach ($localeCrossPoints as $v) {
				if ($startPoint['map_data_latlng'] == $v['point']) {
					$crossTmp = $v;
					break;
				}
				$this->reset();
				$this->setPos($startPoint['map_data_latlng'], $v['point']);
				$this->search();
				$pathCount = count($this->path);
				if ($pathCount == 0) {
					continue;
				}
				if ($crossCount == 0 || $pathCount < $crossCount) {
					$crossCount = $pathCount;
					$crossTmp = $v;
				}
			}
			$this->crossPoints[$crossTmp['mapid']][$crossTmp['mapid'] . $crossTmp['point']] = $crossTmp;
		} else {
			if (!empty($crossPoints)) {
				self::checkCrossPoint($crossPoints, $startPoint, $crossXml);
			}
		}
	}

	public function checkCrossPoint($crossPoints, $startPoint, $crossXml)
	{
		$localeCrossPoints = $newCrossPoint = array();
		foreach ($crossPoints as $v) {
			foreach ($crossXml['map'] as $k => $cv) {
				foreach ($cv['cango'] as $go) {
					if ($go['mapid'] == $v['mapid']) {
						if ($cv['mapid'] == $startPoint['map_id']) {
							if (!array_key_exists($cv['mapid'] . $cv['point'], $localeCrossPoints)) {
								$localeCrossPoints[$cv['mapid'] . $cv['point']] = $cv;
							}
						} else {
							if (!array_key_exists($cv['mapid'] . $cv['point'], $newCrossPoint)) {
								$newCrossPoint[$cv['mapid'] . $cv['point']] = $cv;
							}
						}
						if (!array_key_exists($v['mapid'], $this->crossPoints)) {
							$this->crossPoints[$v['mapid']] = array();
						}
						if (!array_key_exists($v['mapid'] . $v['point'], $this->crossPoints[$v['mapid']])) {
							$this->crossPoints[$v['mapid']][$v['mapid'] . $v['point']] = $v;
						}
					}
				}
			}
		}
		if (!empty($localeCrossPoints)) {
			$crossCount = 0;
			$crossTmp = array();
			foreach ($localeCrossPoints as $v) {
				if ($startPoint['map_data_latlng'] == $v['point']) {
					$this->crossPoints[] = $v;
					break;
				}
				$this->reset();
				$this->setPos($startPoint['map_data_latlng'], $v['point']);
				$this->search();
				$pathCount = count($this->path);
				if ($pathCount == 0) {
					continue;
				}
				if ($crossCount == 0 || $pathCount < $crossCount) {
					$crossCount = $pathCount;
					$crossTmp = $v;
				}
			}
			$this->crossPoints[$crossTmp['mapid']][$crossTmp['mapid'] . $crossTmp['point']] = $crossTmp;
		} else {
			if (!empty($newCrossPoint)) {
				self::checkCrossPoint($newCrossPoint, $startPoint, $crossXml);
			}
		}

	}

	public function reset()
	{
		$this->s_path = $this->openlist = $this->closelist = $this->p_start = $this->p_end = array();
	}


	public function search()
	{
		if (empty($this->s_path)) {
			return false;
		}
		$round = $this->getRound($this->s_path[3]);
		$this->getF($round);
		while (1) {
			if (count($this->openlist) == 0) {
				return false;
			}
			$this->Sort($this->openlist);
			$this->s_path = $this->openlist[count($this->openlist) - 1];
			$this->closelist[] = $this->s_path;
			array_pop($this->openlist);

			if (($this->s_path[3][0] == $this->p_end[0]) && ($this->s_path[3][1] == $this->p_end[1])) {
				$this->getPath();
				return true;
				break;
			} else {
				$round = $this->getRound($this->s_path[3]);
				if ($this->s_path[3][0] == 32 && $this->s_path[3][1] == 31) {
				}

				$this->getF($round);
			}
		}

	}

	public static function direction($startPos, $endPos, $rotation = 0, $baseViewpoint = 30)
	{
		$startPos = explode(',', $startPos);
		$endPos = explode(',', $endPos);
		$differX = $endPos[0] - $startPos[0];
		$differY = $endPos[1] - $startPos[1];
		$additional = 0;
		if ($endPos[0] == $startPos[0]) {
			if ($endPos[1] > $startPos[1]) {
				$additional = 2;
			}
		} elseif ($endPos[1] == $startPos[1]) {
			if ($endPos[0] > $startPos[0]) {
				$additional = 1;
			} elseif ($endPos[0] < $startPos[0]) {
				$additional = 3;
			}
		} elseif ($endPos[0] > $startPos[0]) {
			if ($endPos[1] > $startPos[1]) {
				$additional = 1;
			} elseif ($endPos[1] < $startPos[1]) {
				$additional = 2;
			}
		}elseif ($endPos[0]<$startPos[0]){
			if ($endPos[1]>$startPos[1]){
				$additional = 2;
			}elseif ($endPos[1]<$startPos[1]){
				$additional = 3;
			}
		}
		$viewPoint = (atan2(abs($differY), abs($differX)) * 180) / M_PI;
		$rotation = $rotation%360;
		$viewPoint += ($additional * 90 - $baseViewpoint - $rotation);

		if ($viewPoint < 0) {
			$viewPoint += 360;
		}
		if (($viewPoint > 315 && $viewPoint <= 360) || ($viewPoint >= 0 && $viewPoint < 45)) {
			return self::DIRECTION_UP;
		} elseif ($viewPoint >= 45 && $viewPoint <= 135) {
			return self::DIRECTION_RIGHT;
		} elseif ($viewPoint > 135 && $viewPoint < 225) {
			return self::DIRECTION_DOWN;
		} elseif ($viewPoint > 225 && $viewPoint <= 315) {
			return self::DIRECTION_LEFT;
		}
	}
}