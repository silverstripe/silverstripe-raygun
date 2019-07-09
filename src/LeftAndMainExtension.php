<?php

namespace SilverStripe\Raygun;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;

/**
 * Raygun crash reporting front-end integration for silverstripe/admin
 */
class LeftAndMainExtension extends Extension
{
    /**
     * It may seem weird we're using this extension point to register raygun, but
     * that's important to register it before the other scripts start executing,
     * otherwise we may miss some errors in the bundles
     */
    public function accessedCMS()
    {
        $apiKey = Environment::getEnv('SS_RAYGUN_APP_KEY');

        if (empty($apiKey)) {
            Requirements::insertHeadTags('<!-- Raygun: SS_RAYGUN_APP_KEY is undefined -->');
        } else {
            Requirements::insertHeadTags($htmlBlock = <<<HTML
<!-- Raygun -->
<script type="text/javascript">
  !function(a,b,c,d,e,f,g,h){a.RaygunObject=e,a[e]=a[e]||function(){
  (a[e].o=a[e].o||[]).push(arguments)},f=b.createElement(c),g=b.getElementsByTagName(c)[0],
  f.async=1,f.src=d,g.parentNode.insertBefore(f,g),h=a.onerror,a.onerror=function(b,c,d,f,g){
  h&&h(b,c,d,f,g),g||(g=new Error(b)),a[e].q=a[e].q||[],a[e].q.push({
  e:g})}}(window,document,"script","//cdn.raygun.io/raygun4js/raygun.min.js","rg4js");
</script>
<!-- End Raygun -->

<!-- Raygun crash reporting -->
<script type="text/javascript">
  rg4js('apiKey', '$apiKey');
  rg4js('enableCrashReporting', true);
</script>
<!-- End Raygun crash reporting -->
HTML, hash('crc32', $htmlBlock).'-'.$apiKey
            );
        }
    }
}
