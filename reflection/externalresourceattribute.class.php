<?

/**
 * 
 */
class ExternalResourceAttribute extends ResourceAttribute
{
	function Resolve()
	{
		return $this->Path;
	}
}
