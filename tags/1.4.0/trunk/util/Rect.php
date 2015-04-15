<?php
class Rect {

    private $width;

    private $height;


    function getWidth() {

        return $this->width;
    }

    function getHeight() {

        return $this->height;
    }

    private $ratio;
    /**
     * 縦横比を計算して返却します。  
     */
    private function getRatio() {

        if (!is_null($this->ratio)) return $this->ratio;

        if (is_null($this->getWidth()) || is_null($this->getHeight())) {
            $ratio = array(0, 0);
            return $ratio;
        }

        if ($this->getWidth() <= 0 || $this->getHeight() <= 0) {
            $ratio = array(0, 0);
            return $ratio;
        }

        // 最大公約数を取得し、除算して返却  
        $gcd = $this->getGcd($this->getWidth(), $this->getHeight());

        return array($this->getWidth() / $gcd, $this->getHeight() / $gcd);
    }

    private function getGcd($a, $b) {

        if ($a == 0 || $b == 0) {
            return abs( max(abs($a), abs($b)) );
        }

        $r = $a % $b;

        return ($r != 0) ? $this->getGcd($b, $r) : abs($b);
    }

    function getRatioWidth() {
        $ratio = $this->getRatio();
        return $ratio[0];
    }

    function getRatioHeight() {
        $ratio = $this->getRatio();
        return $ratio[1];
    }

    function __construct($width, $height){
        $this->width = $width;
        $this->height = $height;
    }
}
?>
