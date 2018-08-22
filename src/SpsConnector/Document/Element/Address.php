<?php
declare(strict_types=1);

namespace SpsConnector\Document\Element;

use SimpleXMLElement;
use SpsConnector\Document\Exception\ElementInvalid;
use SpsConnector\Document\Exception\ElementNotSet;

/**
 * Address element
 */
class Address implements ExportsXmlInterface, ImportsXmlInterface
{
    const TYPE_BILL_TO      = 'BT';
    const TYPE_SHIP_TO      = 'ST';
    const TYPE_SHIP_FROM    = 'SF';
    const TYPE_BUYING_PARTY = 'BY';

    const LOCATION_QUALIFIER_BUYER = '92';

    public $typeCode;
    public $locationQualifier;
    public $locationNumber;
    public $name;
    public $street1;
    public $street2;
    public $city;
    public $state;
    public $postalCode;
    public $country = 'USA';

    /**
     * Does this address represent a store location only (no address/city/state/etc)?
     *
     * @var bool
     */
    public $isLocationOnly = false;

    /**
     * Populate this address from the given XML.
     *
     * @param SimpleXMLElement $root
     */
    public function importFromXml(SimpleXMLElement $root): void
    {
        if ($root->getName() != 'Address') {
            throw new ElementInvalid('Address root is invalid.');
        }
        $this->typeCode = (string)$root->AddressTypeCode;
        $this->locationQualifier = (string)$root->LocationCodeQualifier;
        $this->locationNumber = (string)$root->AddressLocationNumber;
        $this->name = (string)$root->AddressName;
        $this->street1 = (string)$root->Address1;
        $this->street2 = (string)$root->Address2;
        $this->city = (string)$root->City;
        $this->state = (string)$root->State;
        $this->postalCode = (string)$root->PostalCode;
        if (isset($root->Country)) {
            $this->country = (string)$root->Country;
        }
    }

    /**
     * Adds this address's data to a new Address element which is then added to the given parent. The Address element
     * is returned.
     *
     * @param SimpleXMLElement $parent
     * @return SimpleXMLElement
     */
    public function exportToXml(SimpleXMLElement $parent): SimpleXMLElement
    {
        if ($this->typeCode != self::TYPE_BILL_TO && $this->typeCode != self::TYPE_SHIP_FROM
            && $this->typeCode != self::TYPE_SHIP_TO && $this->typeCode != self::TYPE_BUYING_PARTY) {
            throw new ElementInvalid('Invalid type code.');
        }
        $root = $parent->addChild('Address');
        $this->addChild($root, 'AddressTypeCode', $this->typeCode, true);
        $this->addChild($root, 'LocationCodeQualifier', $this->locationQualifier, false || $this->isLocationOnly);
        $this->addChild($root, 'AddressLocationNumber', $this->locationNumber, false || $this->isLocationOnly);
        $this->addChild($root, 'AddressName', $this->name, true && !$this->isLocationOnly);
        $this->addChild($root, 'Address1', $this->street1, true && !$this->isLocationOnly);
        $this->addChild($root, 'Address2', $this->street2, false);
        $this->addChild($root, 'City', $this->city, true && !$this->isLocationOnly);
        $this->addChild($root, 'State', $this->state, true && !$this->isLocationOnly);
        $this->addChild($root, 'PostalCode', $this->postalCode, true && !$this->isLocationOnly);
        $this->addChild($root, 'Country', $this->country, false);
        return $root;
    }

    protected function addChild(SimpleXMLElement $parent, string $name, ?string $value, bool $required = true): void
    {
        if ($required && !$value) {
            throw new ElementNotSet(sprintf('Element "%s" is required in an address.', $name));
        }
        if ($value) {
            $parent->addChild($name, $value);
        }
    }
}