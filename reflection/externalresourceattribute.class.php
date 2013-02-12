<?

/**
 * Specifies an external resource like JS APIs from google or stuff
 */
class ExternalResourceAttribute extends ResourceAttribute
{
	function Resolve()
	{
		return $this->Path;
	}
}
