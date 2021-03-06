<?php

namespace Silo\Inventory\Collection;

use Silo\Inventory\Model\Modifier;

/**
 * Advanced operations on Operations ArrayCollection.
 */
class ModifierCollection extends ArrayCollection
{
    public function containsName($name)
    {
        foreach ($this->toArray() as $modifier) {/** @var Modifier $modifier */
            if ($modifier->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $name
     * @return null|Modifier
     */
    public function getByName($name)
    {
        foreach ($this->toArray() as $modifier) {/** @var Modifier $modifier */
            if ($modifier->getName() === $name) {
                return $modifier;
            }
        }

        return null;
    }
}
