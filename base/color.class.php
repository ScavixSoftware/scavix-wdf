<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Base;

/**
 * Helper class to deal with HTML colors.
 */
class Color
{
    var $r, $g, $b, $a;
    
    protected function __construct($parts,$convert_alpha=false)
    {
        if( !is_array($parts) || count($parts)!=4 )
            \ScavixWDF\WdfException::Raise("Invalid arguments ".json_encode($parts));
        if( min($parts)<0 || max($parts)>255 )
            \ScavixWDF\WdfException::Raise("Argument out of range ".json_encode($parts));
        list($this->r,$this->g,$this->b,$this->a) = $parts;
        if( $convert_alpha ) 
            $this->a /= 255;
        if( $this->a>1 )
            \ScavixWDF\WdfException::Raise("Invalid alpha '{$this->a}': must be 0-1");
    }
    
    /**
     * Composes a Color object from a valid HTML color string.
     * 
     * @param string $hex_string Valid HTML color string
     * @return Color
     */
    public static function hex($hex_string)
    {
        static $named = [
            "maroon" => "800000", "darkred" => "8b0000", "firebrick" => "b22222",
            "red" => "ff0000", "salmon" => "fa8072", "tomato" => "ff6347",
            "coral" => "ff7f50", "orangered" => "ff4500", "chocolate" => "d2691e",
            "sandybrown" => "f4a460", "darkorange" => "ff8c00", "orange" => "ffa500",
            "darkgoldenrod" => "b8860b", "goldenrod" => "daa520", "gold" => "ffd700",
            "olive" => "808000", "yellow" => "ffff00", "yellowgreen" => "9acd32",
            "greenyellow" => "adff2f", "chartreuse" => "7fff00", "lawngreen" => "7cfc00",
            "green" => "008000", "lime" => "00ff00", "limegreen" => "32cd32",
            "springgreen" => "00ff7f", "mediumspringgreen" => "00fa9a", "turquoise" => "40e0d0",
            "lightseagreen" => "20b2aa", "mediumturquoise" => "48d1cc", "teal" => "008080",
            "darkcyan" => "008b8b", "aqua" => "00ffff", "cyan" => "00ffff",
            "darkturquoise" => "00ced1", "deepskyblue" => "00bfff", "dodgerblue" => "1e90ff",
            "royalblue" => "4169e1", "navy" => "000080", "darkblue" => "00008b",
            "mediumblue" => "0000cd", "blue" => "0000ff", "blueviolet" => "8a2be2",
            "darkorchid" => "9932cc", "darkviolet" => "9400d3", "purple" => "800080", 
            "darkmagenta" => "8b008b", "fuchsia" => "ff00ff", "magenta" => "ff00ff",
            "mediumvioletred" => "c71585", "deeppink" => "ff1493", "hotpink" => "ff69b4",
            "crimson" => "dc143c", "brown" => "a52a2a", "indianred" => "cd5c5c",
            "rosybrown" => "bc8f8f", "lightcoral" => "f08080", "snow" => "fffafa",
            "mistyrose" => "ffe4e1", "darksalmon" => "e9967a", "lightsalmon" => "ffa07a",
            "sienna" => "a0522d", "seashell" => "fff5ee", "saddlebrown" => "8b4513",
            "peachpuff" => "ffdab9", "peru" => "cd853f", "linen" => "faf0e6",
            "bisque" => "ffe4c4", "burlywood" => "deb887", "tan" => "d2b48c",
            "antiquewhite" => "faebd7", "navajowhite" => "ffdead", "blanchedalmond" => "ffebcd",
            "papayawhip" => "ffefd5", "moccasin" => "ffe4b5", "wheat" => "f5deb3",
            "oldlace" => "fdf5e6", "floralwhite" => "fffaf0", "cornsilk" => "fff8dc",
            "khaki" => "f0e68c", "lemonchiffon" => "fffacd", "palegoldenrod" => "eee8aa",
            "darkkhaki" => "bdb76b", "beige" => "f5f5dc", "lightgoldenrodyellow" => "fafad2",
            "lightyellow" => "ffffe0", "ivory" => "fffff0", "olivedrab" => "6b8e23",
            "darkolivegreen" => "556b2f", "darkseagreen" => "8fbc8f", "darkgreen" => "006400",
            "forestgreen" => "228b22", "lightgreen" => "90ee90", "palegreen" => "98fb98",
            "honeydew" => "f0fff0", "seagreen" => "2e8b57", "mediumseagreen" => "3cb371",
            "mintcream" => "f5fffa", "mediumaquamarine" => "66cdaa", "aquamarine" => "7fffd4",
            "darkslategray" => "2f4f4f", "paleturquoise" => "afeeee", "lightcyan" => "e0ffff",
            "azure" => "f0ffff", "cadetblue" => "5f9ea0", "powderblue" => "b0e0e6",
            "lightblue" => "add8e6", "skyblue" => "87ceeb", "lightskyblue" => "87cefa",
            "steelblue" => "4682b4", "aliceblue" => "f0f8ff", "slategray" => "708090",
            "lightslategray" => "778899", "lightsteelblue" => "b0c4de", "cornflowerblue" => "6495ed",
            "lavender" => "e6e6fa", "ghostwhite" => "f8f8ff", "midnightblue" => "191970",
            "slateblue" => "6a5acd", "darkslateblue" => "483d8b", "mediumslateblue" => "7b68ee",
            "mediumpurple" => "9370db", "indigo" => "4b0082", "mediumorchid" => "ba55d3",
            "plum" => "dda0dd", "violet" => "ee82ee", "thistle" => "d8bfd8",
            "orchid" => "da70d6", "lavenderblush" => "fff0f5", "palevioletred" => "db7093",
            "pink" => "ffc0cb", "lightpink" => "ffb6c1", "black" => "000000",
            "dimgray" => "696969", "gray" => "808080", "darkgray" => "a9a9a9",
            "silver" => "c0c0c0", "lightgrey" => "d3d3d3", "gainsboro" => "dcdcdc",
            "whitesmoke" => "f5f5f5", "white" => "ffffff"
        ];
        
        
        $hex = strtolower(trim(trim("$hex_string",'#')));
        if( isset($named[$hex]) )
            $hex = $named[$hex];
        if( $hex == "transparent" )
            return new Color([0,0,0,0]);
        
        $hex = preg_replace('/^([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])$/i','$1$1$2$2$3$3$4$4', $hex);
        $hex = preg_replace('/^([0-9a-f])([0-9a-f])([0-9a-f])$/i','$1$1$2$2$3$3', $hex);
        if( strlen($hex) < 8 )
            $hex .= "ff";
        
        if( preg_match('/([^0-9a-f])/i',$hex) )
            \ScavixWDF\WdfException::Raise("Invalid color code: $hex_string");
        
        $parts = array_map('hexdec', str_split($hex,2));
        return new Color($parts,true);
    }

    /**
     * Composes a <Color> object from RGBA values.
     * 
     * @param int $r Red component (0-255)
     * @param int $g Green component (0-255)
     * @param int $b Blue component (0-255)
     * @param float $a Alpha (0-1)
     * @return \ScavixWDF\Base\Color
     */
    public static function rgba($r,$g,$b,$a=1)
    {
        return new Color([$r,$g,$b,$a]);
    }
    
    /**
     * Composes a <ColorRange> object.
     * 
     * @param mixed $from Optional string or <Color> defining the start
     * @param mixed $to Optional string or <Color> defining the end
     * @return \ScavixWDF\Base\Color\ColorRange
     */
    public static function range($from,$to)
    {
        $from = $from instanceof Color?$from:Color::hex($from);
        $to = $to instanceof Color?$to:Color::hex($to);
        return new Color\ColorRange($from,$to);
    }
    
    /**
     * Composes a random <Color>.
     * 
     * @param mixed $min Optional string or <Color> defining the minimum
     * @param mixed $max Optional string or <Color> defining the maximum
     * @return \ScavixWDF\Base\Color
     */
    public static function random($min=false,$max=false)
    {
        if( $min && !($min instanceof Color) ) $min = Color::hex($min);
        if( $max && !($max instanceof Color) ) $max = Color::hex($max);
        if( !$min ) $min = Color::rgba(0, 0, 0);
        if( !$max ) $max = Color::rgba(255, 255, 255);
        
        $parts = [];
        foreach( ['r','g','b'] as $p )
            $parts[$p] = random_int($min->$p,$max->$p);
        extract($parts);
        return Color::rgba($r,$g,$b);
    }
    
    public function __toString()
    {
        $t = "{$this->r},{$this->g},{$this->b},{$this->a}";
        switch( $t )
        {
            case "0,0,0,1": return "#000";
            case "255,255,255,1": return "#FFF";
        }
        if( $this->a == 1 )
            return sprintf("#%02X%02X%02X",$this->r,$this->g,$this->b);
        return "rgba($t)";
    }
    
    public function setAlpha($a)
    {
        $c = new Color([0,0,0,$a],$a>1);
        $this->a = $c->a;
        return $this;
    }
    
    public static function matchingFont(Color $c)
    {
        if( ($c->r + $c->g + $c->b) / 3 > 100 )
            return new Color([0,0,0,1]);
        return new Color([255,255,255,1]);
    }
}

namespace ScavixWDF\Base\Color;

/**
 * Represents a color range.
 * 
 * Never construct a ColorRange directly, but use <Color::range>, 
 * otherwise the classloader may fail.
 */
class ColorRange
{
    var $from, $to, $min, $max;
    
    function __toString()
    {
        if( $this->min && $this->max)
            return "ColorRange {$this->from}->{$this->to} ({$this->min}->{$this->max})";
        return "ColorRange {$this->from}->{$this->to}";
    }
    
    public function __construct(\ScavixWDF\Base\Color $from, \ScavixWDF\Base\Color $to)
    {
        $this->from = $from; 
        $this->to = $to;
    }
    
    /**
     * Sets values that act as min and max when querying data.
     * 
     * @param int|float $min Minimum value
     * @param int|float $max Maximum value
     * @return ColorRange $this
     */
    public function setMinMax($min,$max)
    {
        $this->min = min($min,$max);
        $this->max = max($min,$max);
        return $this;
    }
    
    /**
     * Return the color corresponding to a value in the range.
     * 
     * @param int|float $value The value (between min and max) to get the color for
     * @return Color
     */
    public function fromValue($value)
    {
        $t = max($this->max-$this->min,0.00000001);
        $v = $value - $this->min;
        return $this->fromPercent($v / $t * 100);
    }
    
    /**
     * Return the color corresponding to percent in the range.
     * 
     * @param int|float $percent The percent (0-100) to get the color for
     * @return Color
     */
    public function fromPercent($percent)
    {
        $parts = [];
        foreach( ['r','g','b','a'] as $p )
        {
            $s = ($this->to->$p - $this->from->$p) / 100;
            $parts[$p] = $p == 'a'
                ?max(0,min(round($this->from->$p + ($percent * $s),4),1))
                :max(0,min((int)round($this->from->$p + ($percent * $s)),255));
        }
        extract($parts);
        return \ScavixWDF\Base\Color::rgba($r,$g,$b,$a);
    }
}
