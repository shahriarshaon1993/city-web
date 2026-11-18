<?php

namespace App\Support\Html;

use HTMLPurifier_HTMLDefinition;
use Stevebauman\Purify\Definitions\Definition;
use Stevebauman\Purify\Definitions\Html5Definition;

/**
 * NFR-SEC-03: the HTML5 elements the news editor produces (figure, figcaption) and the data
 * attributes of its library image and YouTube nodes, which HTMLPurifier does not know.
 */
final class ArticleDefinition implements Definition
{
    public static function apply(HTMLPurifier_HTMLDefinition $definition): void
    {
        Html5Definition::apply($definition);

        $definition->addAttribute('img', 'data-asset-id', 'Number');
        $definition->addAttribute('img', 'data-decorative', 'Text');
        $definition->addAttribute('div', 'data-youtube-video', 'Text');
        $definition->addAttribute('iframe', 'allowfullscreen', 'Text');
    }
}
