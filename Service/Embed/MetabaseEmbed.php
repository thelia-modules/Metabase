<?php

namespace Metabase\Service\Embed;

class MetabaseEmbed
{
    private string $url;

    public bool $title;

    public string $width;
    public string $height;

    /**
     * Default constructor.
     *
     * @param string $url    Base url for the Metabase installation
     * @param bool   $title  Show dashboard/question title (default = false)
     * @param string $width  Set css width of dashboard/question (default = 100%)
     * @param string $height Set css height of dashboard/question (default = 800)
     */
    public function __construct(string $url, bool $title = false, string $width = '100%', string $height = '800')
    {
        $this->url = $url;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Generate the HTML to embed a dashboard iframe with a given dashboard id.
     * It assumes no iframe border. Size can be manipulated via
     * class $width/$height.
     *
     * @return string Code to embed
     */
    public function dashboardIFrame($uuid): string
    {
        return $this->iframe($this->url.'/public/dashboard/'.$uuid);
    }

    /**
     * Generate the HTML to embed an iframe with a given URL.
     * It assumes no iframe border. Size can be manipulated via
     * class $width/$height.
     *
     * @param string $iframeUrl The URL to create an iframe for
     *
     * @return string Code to embed
     */
    protected function iframe(string $iframeUrl): string
    {
        return '<iframe
            src="'.$iframeUrl.'"
            frameborder="0"
            width="'.$this->width.'"
            height="'.$this->height.'"
            lang="fr"
            allowtransparency></iframe>';
    }
}
