<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author     Jérémie Augustin <jeremie.augustin@pixel-cookers.com>
 */
class ManyToManyRemoveObjectBehavior extends Behavior
{
    public function objectMethods($builder)
    {
        $script = '';
        foreach ($this->getTable()->getCrossFks() as $fkList) {
            list($refFK, $crossFK) = $fkList;

            $script .= $this->addCrossFKRemove($builder, $refFK, $crossFK);
        }

        return $script;
    }

    /**
     * Adds the method that remove an object from the referrer fkey collection.
     * @param      string $script The script will be modified in this method.
     */
    protected function addCrossFKRemove($builder, ForeignKey $refFK, ForeignKey $crossFK)
    {
        $relCol = $builder->getFKPhpNameAffix($crossFK, $plural = true);
        $collName = 'coll' . $relCol;

        $tblFK = $refFK->getTable();

        $joinedTableObjectBuilder = $builder->getNewObjectBuilder($refFK->getTable());
        $className = $joinedTableObjectBuilder->getObjectClassname();

        $M2MScheduledForDeletion = lcfirst($relCol) . "ScheduledForDeletion";

        $crossObjectName = '$' . $crossFK->getForeignTable()->getStudlyPhpName();
        $crossObjectClassName = $builder->getNewObjectBuilder($crossFK->getForeignTable())->getObjectClassname();
        $crossObjectFQCN = '\\' . $builder->getNewObjectBuilder($crossFK->getForeignTable())->getStubObjectBuilder()->getFullyQualifiedClassname();

        $relatedObjectClassName = $builder->getFKPhpNameAffix($crossFK, $plural = false);

        return "
/**
 * Remove a " . $crossObjectClassName . " object to this object
 * through the " . $tblFK->getName() . " cross reference table.
 *
 * @param      " . $crossObjectClassName . " " . $crossObjectName . " The $className object to relate
 * @return     void
 */
public function remove{$relatedObjectClassName}($crossObjectFQCN $crossObjectName)
{
	if (\$this->" . $collName . " === null) {
		\$this->init" . $relCol . "();
	}
	if (\$this->" . $collName . "->contains(" . $crossObjectName . ")) {
		\$this->" . $collName . "->remove(\$this->" . $collName . "->search(" . $crossObjectName . ")); //we remove the object from the collection
		if (null === \$this->" . $M2MScheduledForDeletion . ") {
			\$this->" . $M2MScheduledForDeletion . " = clone \$this->" . $collName . ";
			\$this->" . $M2MScheduledForDeletion . "->clear();
		}
		\$this->" . $M2MScheduledForDeletion . "[]= " . $crossObjectName . ";
	}
}
";
    }
}
