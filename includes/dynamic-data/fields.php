<?php
/**
 * Třídy dynamických polí Breakdance (od 0.15.0). Načítá se až po ověření, že Breakdance běží
 * (includes/dynamic-data.php) – třídy dědí z Breakdance\DynamicData\*.
 * Jedna třída obslouží víc polí: hodnotu počítá callback předaný v konstruktoru.
 */

namespace MartinDV;

use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;
use Breakdance\DynamicData\ImageField;
use Breakdance\DynamicData\ImageData;

if (!defined('ABSPATH')) {
    exit;
}

class DynText extends StringField
{
    private $slug;
    private $label;
    private $sub;
    private $cb;
    private $types;

    /**
     * @param string   $slug  unikátní slug (marmal_dv_…)
     * @param string   $label název v nabídce dynamických dat
     * @param string   $sub   podkategorie (Jednotka / Projekt)
     * @param callable $cb    vrací hotový text (neescapovaný) nebo URL
     * @param array    $types returnTypes – ['string'] nebo ['url']
     */
    public function __construct(string $slug, string $label, string $sub, callable $cb, array $types = ['string'])
    {
        $this->slug  = $slug;
        $this->label = $label;
        $this->sub   = $sub;
        $this->cb    = $cb;
        $this->types = $types;
    }

    public function label()
    {
        return $this->label;
    }

    public function category()
    {
        return __('Developerské projekty', 'martin-dev-vyber-bytu');
    }

    public function subcategory()
    {
        return $this->sub;
    }

    public function slug()
    {
        return $this->slug;
    }

    public function returnTypes()
    {
        return $this->types;
    }

    public function handler($attributes): StringData
    {
        $value = (string) call_user_func($this->cb);
        if ($value === '') {
            return StringData::emptyString();
        }
        // URL přes esc_url (jen http(s), tel:, mailto:), text escapovaný – hodnoty jsou od editorů webu.
        return StringData::fromString(in_array('url', $this->types, true) ? esc_url($value, ['http', 'https', 'tel', 'mailto']) : esc_html($value));
    }
}

class DynImage extends ImageField
{
    private $slug;
    private $label;
    private $sub;
    private $cb;

    public function __construct(string $slug, string $label, string $sub, callable $cb)
    {
        $this->slug  = $slug;
        $this->label = $label;
        $this->sub   = $sub;
        $this->cb    = $cb;
    }

    public function label()
    {
        return $this->label;
    }

    public function category()
    {
        return __('Developerské projekty', 'martin-dev-vyber-bytu');
    }

    public function subcategory()
    {
        return $this->sub;
    }

    public function slug()
    {
        return $this->slug;
    }

    public function handler($attributes): ImageData
    {
        $id = (int) call_user_func($this->cb);
        return $id ? ImageData::fromAttachmentId($id) : new ImageData();
    }
}
