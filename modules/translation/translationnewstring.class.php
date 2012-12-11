<?

class TranslationNewString extends Template
{
	function __initialize($term,$hits,$last_hit)
    {
        parent::__initialize();
        $this->set("term",$term);
        $this->set("hits",$hits);
        $this->set("last_hit",$last_hit);
    }
}