<?

/**
 * Specifies that a resource file is needed.
 */
class ResourceAttribute extends System_Attribute
{
	var $Path;
	
	function __construct($path)
	{
		$this->Path = $path;
	}
	
	function Resolve()
	{
		return resFile($this->Path);
	}
	
	public static function Collect($classname,$pre="*")
	{
		$ref = System_Reflector::GetInstance($classname);
		$attrs = $ref->GetClassAttributes(array('Resource','ExternalResource'));
		$ref = $ref->getParentClass();
		$parents = $ref?self::Collect($ref->getName(),"*$pre"):array();
		$attrs = array_merge($parents,$attrs);
		return $attrs;
	}
	
	public static function ResolveAll($array_of_res_attr)
	{
		$res = array();
		foreach( $array_of_res_attr as $a )
			$res[] = $a->Resolve();
		return array_unique($res);
	}
}
