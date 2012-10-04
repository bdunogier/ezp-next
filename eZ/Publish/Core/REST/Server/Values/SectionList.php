<?php
/**
 * File containing the SectionList class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Values;

use eZ\Publish\Core\REST\Server\Value as RestValue;

/**
 * Section list view model
 */
class SectionList extends RestValue
{
    /**
     * Sections
     *
     * @var \eZ\Publish\API\Repository\Values\Content\Section[]
     */
    public $sections;

    /**
     * Construct
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Section[] $sections
     */
    public function __construct( array $sections )
    {
        $this->sections = $sections;
    }
}
